<?php

namespace App\Repository;

use App\Entity\CommunityPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityPost>
 */
class CommunityPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityPost::class);
    }

    /**
     * @return CommunityPost[]
     */
    public function findFiltered(?string $type = null, ?string $sport = null, ?string $search = null, string $sort = 'recent'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.communityLikes', 'l')
            ->addSelect('l')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($type) {
            $qb->andWhere('p.type = :type')->setParameter('type', strtoupper($type));
        }

        if ($sport) {
            $qb->andWhere('p.sport = :sport')->setParameter('sport', $sport);
        }

        if ($search) {
            $qb->andWhere('p.content LIKE :search OR u.nickname LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($sort === 'popular') {
            $qb->orderBy('SIZE(p.communityLikes)', 'DESC')->addOrderBy('p.createdAt', 'DESC');
        } else {
            $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Classement des membres les plus actifs, calculé à partir de leurs
     * publications et des likes/commentaires reçus (pas de faux "points").
     *
     * @return array<int, array{user: \App\Entity\User, score: int, posts: int}>
     */
    public function getTopContributors(?\DateTimeInterface $since, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.communityLikes', 'l')
            ->leftJoin('p.communityComments', 'c')
            ->leftJoin('p.user', 'u')
            ->select('u.id AS userId', 'u.nickname AS nickname')
            ->addSelect('COUNT(DISTINCT p.id) AS postCount', 'COUNT(DISTINCT l.id) AS likeCount', 'COUNT(DISTINCT c.id) AS commentCount')
            ->groupBy('u.id', 'u.nickname');

        if ($since) {
            $qb->andWhere('p.createdAt >= :since')->setParameter('since', $since);
        }

        $rows = $qb->getQuery()->getResult();

        $ranking = array_map(static function (array $row) {
            return [
                'user' => ['id' => (int) $row['userId'], 'nickname' => $row['nickname']],
                'posts' => (int) $row['postCount'],
                'score' => (int) $row['postCount'] * 2 + (int) $row['likeCount'] + (int) $row['commentCount'],
            ];
        }, $rows);

        usort($ranking, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($ranking, 0, $limit);
    }

    /**
     * @return array<string, int>
     */
    public function countBySport(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.sport AS sport, COUNT(p.id) AS total')
            ->groupBy('p.sport')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['sport']] = (int) $row['total'];
        }

        return $counts;
    }
}
