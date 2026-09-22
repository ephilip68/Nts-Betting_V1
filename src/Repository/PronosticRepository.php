<?php

namespace App\Repository;

use App\Entity\Pronostic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pronostic>
 */
class PronosticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pronostic::class);
    }

    /**
     * Liste paginée + filtrée pour la page publique /pronostics.
     *
     * @return array{items: Pronostic[], total: int}
     */
    public function findFilteredPaginated(
        ?string $sport = null,
        ?string $access = null, // 'free' | 'vip' | null
        int $page = 1,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.matchDate', 'DESC');

        if ($sport) {
            $qb->andWhere('p.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($access === 'free') {
            $qb->andWhere('p.isVip = false');
        } elseif ($access === 'vip') {
            $qb->andWhere('p.isVip = true');
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }

    /**
     * Le pronostic "du jour" mis en avant (gratuit, affiché dans la sidebar).
     */
    public function findFeatured(): ?Pronostic
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isFeatured = true')
            ->orderBy('p.matchDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Le prochain pronostic VIP à venir (teaser dans la sidebar pour les non-abonnés).
     */
    public function findNextVip(): ?Pronostic
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isVip = true')
            ->andWhere('p.matchDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('p.matchDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
