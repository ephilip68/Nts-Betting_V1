<?php

namespace App\Repository;

use App\Entity\Bankroll;
use App\Entity\VaultEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VaultEntry>
 */
class VaultEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VaultEntry::class);
    }

    /**
     * Tous les paris d'un bankroll, du plus ancien au plus récent (base des stats).
     *
     * @return VaultEntry[]
     */
    public function findAllForBankroll(Bankroll $bankroll): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.bankroll = :bankroll')
            ->setParameter('bankroll', $bankroll)
            ->orderBy('v.placedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les paris de tous les bankrolls d'un membre (pour les résumés agrégés
     * hors page Vault, ex : dashboard, profil).
     *
     * @param Bankroll[] $bankrolls
     * @return VaultEntry[]
     */
    public function findAllForBankrolls(array $bankrolls): array
    {
        if ($bankrolls === []) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->andWhere('v.bankroll IN (:bankrolls)')
            ->setParameter('bankrolls', $bankrolls)
            ->orderBy('v.placedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Les paris les plus récents d'un bankroll, pour l'historique des transactions.
     *
     * @return VaultEntry[]
     */
    public function findRecentForBankroll(Bankroll $bankroll, int $limit = 15): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.bankroll = :bankroll')
            ->setParameter('bankroll', $bankroll)
            ->orderBy('v.placedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
