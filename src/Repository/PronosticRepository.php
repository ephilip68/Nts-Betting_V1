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
        int $limit = 10,
        ?string $competition = null,
        ?string $betType = null,
        ?int $minConfidence = null,
        ?string $search = null,
        string $sort = 'recent', // 'recent' | 'oldest' | 'confidence' | 'odds'
        ?array $onlyIds = null // restreint aux IDs donnés (ex : favoris)
    ): array {
        $qb = $this->createQueryBuilder('p');

        match ($sort) {
            'oldest' => $qb->orderBy('p.matchDate', 'ASC'),
            'confidence' => $qb->orderBy('p.confidence', 'DESC')->addOrderBy('p.matchDate', 'DESC'),
            'odds' => $qb->orderBy('p.odds', 'DESC')->addOrderBy('p.matchDate', 'DESC'),
            default => $qb->orderBy('p.matchDate', 'DESC'),
        };

        if ($sport) {
            $qb->andWhere('p.sport = :sport')
                ->setParameter('sport', $sport);
        }

        if ($access === 'free') {
            $qb->andWhere('p.isVip = false');
        } elseif ($access === 'vip') {
            $qb->andWhere('p.isVip = true');
        }

        if ($competition) {
            $qb->andWhere('p.competition = :competition')->setParameter('competition', $competition);
        }

        if ($betType) {
            $qb->andWhere('p.betType = :betType')->setParameter('betType', $betType);
        }

        if ($minConfidence) {
            $qb->andWhere('p.confidence >= :minConfidence')->setParameter('minConfidence', $minConfidence);
        }

        if ($search) {
            $qb->andWhere('p.teamHome LIKE :search OR p.teamAway LIKE :search OR p.competition LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($onlyIds !== null) {
            if ($onlyIds === []) {
                return ['items' => [], 'total' => 0];
            }
            $qb->andWhere('p.id IN (:onlyIds)')->setParameter('onlyIds', $onlyIds);
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
     * @return string[]
     */
    public function findDistinctCompetitions(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.competition')
                ->orderBy('p.competition', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'competition'
        );
    }

    /**
     * @return string[]
     */
    public function findDistinctBetTypes(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.betType')
                ->orderBy('p.betType', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'betType'
        );
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

    /**
     * Tous les pronostics déjà joués (gagné/perdu), du plus ancien au plus récent.
     * Base pour tous les calculs de stats de la page /resultats.
     *
     * @return Pronostic[]
     */
    public function findAllSettled(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('statuses', [Pronostic::STATUS_WON, Pronostic::STATUS_LOST])
            ->orderBy('p.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste paginée + filtrée des pronostics déjà joués, pour le tableau
     * "Derniers résultats" et le futur historique complet.
     *
     * @return array{items: Pronostic[], total: int}
     */
    public function findSettledFiltered(
        ?string $sport = null,
        ?string $competition = null,
        ?string $betType = null,
        ?string $status = null, // 'won' | 'lost' | null
        ?int $periodDays = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('statuses', $status ? [$status] : [Pronostic::STATUS_WON, Pronostic::STATUS_LOST])
            ->orderBy('p.matchDate', 'DESC');

        if ($sport) {
            $qb->andWhere('p.sport = :sport')->setParameter('sport', $sport);
        }

        if ($competition) {
            $qb->andWhere('p.competition = :competition')->setParameter('competition', $competition);
        }

        if ($betType) {
            $qb->andWhere('p.betType = :betType')->setParameter('betType', $betType);
        }

        if ($periodDays) {
            $qb->andWhere('p.matchDate >= :since')
                ->setParameter('since', (new \DateTime())->modify("-{$periodDays} days"));
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
     * @return string[] Sports distincts présents parmi les pronostics déjà joués
     */
    public function findDistinctSettledSports(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.sport')
                ->andWhere('p.status IN (:statuses)')
                ->setParameter('statuses', [Pronostic::STATUS_WON, Pronostic::STATUS_LOST])
                ->orderBy('p.sport', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'sport'
        );
    }

    /**
     * @return string[] Compétitions distinctes présentes parmi les pronostics déjà joués
     */
    public function findDistinctSettledCompetitions(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.competition')
                ->andWhere('p.status IN (:statuses)')
                ->setParameter('statuses', [Pronostic::STATUS_WON, Pronostic::STATUS_LOST])
                ->orderBy('p.competition', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'competition'
        );
    }

    /**
     * @return string[] Types de pari distincts présents parmi les pronostics déjà joués
     */
    public function findDistinctSettledBetTypes(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                ->select('DISTINCT p.betType')
                ->andWhere('p.status IN (:statuses)')
                ->setParameter('statuses', [Pronostic::STATUS_WON, Pronostic::STATUS_LOST])
                ->orderBy('p.betType', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'betType'
        );
    }
}
