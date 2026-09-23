<?php

namespace App\Repository;

use App\Entity\PronosticFavorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PronosticFavorite>
 */
class PronosticFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PronosticFavorite::class);
    }

    /**
     * @return int[] Identifiants des pronostics mis en favori par ce membre
     */
    public function findFavoritePronosticIds(User $user): array
    {
        return array_column(
            $this->createQueryBuilder('f')
                ->select('IDENTITY(f.pronostic) as pronosticId')
                ->andWhere('f.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getScalarResult(),
            'pronosticId'
        );
    }
}
