<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return array{items: Article[], total: int}
     */
    public function findPublishedFiltered(
        ?string $category = null,
        ?string $search = null,
        int $page = 1,
        int $limit = 9
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('a.publishedAt', 'DESC');

        if ($category) {
            $qb->andWhere('a.category = :category')->setParameter('category', $category);
        }

        if ($search) {
            $qb->andWhere('a.title LIKE :search OR a.excerpt LIKE :search')
                ->setParameter('search', '%' . $search . '%');
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
     * @return Article[]
     */
    public function findFeatured(int $limit = 3): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.isFeatured = true')
            ->setParameter('status', 'published')
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Article[]
     */
    public function findSimilar(Article $article, int $limit = 4): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.category = :category')
            ->andWhere('a.id != :id')
            ->setParameter('status', 'published')
            ->setParameter('category', $article->getCategory())
            ->setParameter('id', $article->getId())
            ->orderBy('a.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
