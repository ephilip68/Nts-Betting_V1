<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array{items: User[], total: int}
     */
    public function findFiltered(?string $search = null, ?string $plan = null, int $page = 1, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.dateInscription', 'DESC');

        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.nickname LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($plan === 'admin') {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%ROLE_ADMIN%');
        } elseif ($plan === 'vip_manual') {
            $qb->andWhere('u.vipUntil IS NOT NULL AND u.vipUntil > :now')->setParameter('now', new \DateTime());
        } elseif ($plan) {
            $qb->andWhere('u.subscriptionPlan = :plan AND u.subscriptionStatus = :active')
                ->setParameter('plan', $plan)
                ->setParameter('active', 'active');
        }

        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }
}
