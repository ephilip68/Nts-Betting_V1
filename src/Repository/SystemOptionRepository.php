<?php

namespace App\Repository;

use App\Entity\SystemOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemOption>
 */
class SystemOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemOption::class);
    }

    /**
     * @return SystemOption[]
     */
    public function findForMatchCount(int $matches): array
    {
        return $this->findBy(['matches' => $matches]);
    }
}
