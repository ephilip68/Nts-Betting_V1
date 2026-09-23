<?php

namespace App\Service;

use App\Entity\Bankroll;
use App\Entity\VaultEntry;

/**
 * Calcule les statistiques du NTS Vault d'un membre (solde, ROI, taux de
 * réussite, évolution de la bankroll...) à partir de ses paris enregistrés.
 * Le "solde" est purement calculé (somme des profits/pertes) : il n'y a
 * aucun dépôt d'argent réel sur le site.
 */
class VaultStatsService
{
    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juil', 8 => 'Aoû', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
    ];

    /**
     * Le profit est calculé et persisté par {@see VaultBetCalculator} à l'enregistrement
     * du pari (seul moyen de gérer correctement combinés/systèmes/boost/assurance/cashout).
     */
    public function profit(VaultEntry $entry): float
    {
        return (float) ($entry->getProfit() ?? 0.0);
    }

    private function isSettled(VaultEntry $entry): bool
    {
        return in_array($entry->getStatus(), [VaultEntry::STATUS_WON, VaultEntry::STATUS_LOST, VaultEntry::STATUS_CASHOUT], true);
    }

    /**
     * @param VaultEntry[] $entries Tous les paris du membre, triés par date croissante
     */
    public function getSummary(array $entries): array
    {
        $settled = array_values(array_filter($entries, fn (VaultEntry $e) => $this->isSettled($e)));
        $voidCount = count(array_filter($entries, static fn (VaultEntry $e) => $e->getStatus() === VaultEntry::STATUS_VOID));
        $settledCount = count($settled);

        if ($settledCount === 0) {
            return [
                'balance' => 0.0,
                'totalStaked' => 0.0,
                'totalWon' => 0.0,
                'roi' => null,
                'winRate' => null,
                'settledCount' => 0,
                'wonCount' => 0,
                'lostCount' => 0,
                'voidCount' => $voidCount,
                'avgStake' => null,
                'avgWin' => null,
            ];
        }

        $balance = 0.0;
        $totalStaked = 0.0;
        $totalWon = 0.0;
        $wonCount = 0;
        $lostCount = 0;
        $wonProfitSum = 0.0;

        foreach ($settled as $entry) {
            $profit = $this->profit($entry);
            $stake = (float) $entry->getStake();

            $balance += $profit;
            $totalStaked += $stake;

            if ($profit > 0) {
                $wonCount++;
                $totalWon += $stake + $profit;
                $wonProfitSum += $profit;
            } elseif ($profit < 0) {
                $lostCount++;
            }
        }

        return [
            'balance' => round($balance, 2),
            'totalStaked' => round($totalStaked, 2),
            'totalWon' => round($totalWon, 2),
            'roi' => $totalStaked > 0 ? round(($balance / $totalStaked) * 100, 1) : null,
            'winRate' => round(($wonCount / $settledCount) * 100, 1),
            'settledCount' => $settledCount,
            'wonCount' => $wonCount,
            'lostCount' => $lostCount,
            'voidCount' => $voidCount,
            'avgStake' => round($totalStaked / $settledCount, 2),
            'avgWin' => $wonCount > 0 ? round($wonProfitSum / $wonCount, 2) : null,
        ];
    }

    /**
     * Évolution du solde cumulé sur les `$days` derniers jours, pour le graphique.
     *
     * @param VaultEntry[] $entries
     */
    public function getBalanceEvolution(array $entries, int $days = 30): array
    {
        $since = (new \DateTime())->modify("-{$days} days")->setTime(0, 0);

        // Solde déjà accumulé avant le début de la période affichée
        $runningBalance = 0.0;
        foreach ($entries as $entry) {
            if ($entry->getPlacedAt() < $since) {
                $runningBalance += $this->profit($entry);
            }
        }

        $points = [];
        $cursor = clone $since;
        $today = (new \DateTime())->setTime(0, 0);

        while ($cursor <= $today) {
            $dayEntries = array_filter(
                $entries,
                static fn (VaultEntry $e) => $e->getPlacedAt()->format('Y-m-d') === $cursor->format('Y-m-d')
            );

            foreach ($dayEntries as $entry) {
                $runningBalance += $this->profit($entry);
            }

            $points[] = [
                'label' => $cursor->format('d/m'),
                'balance' => round($runningBalance, 2),
            ];

            $cursor->modify('+1 day');
        }

        return $points;
    }

    /**
     * Progression des objectifs personnels du membre pour le mois en cours.
     *
     * @param VaultEntry[] $entries
     */
    public function getMonthlyGoalsProgress(Bankroll $bankroll, array $entries): array
    {
        $monthStart = new \DateTime('first day of this month midnight');
        $monthEntries = array_values(array_filter(
            $entries,
            fn (VaultEntry $e) => $e->getPlacedAt() >= $monthStart && $this->isSettled($e)
        ));

        $monthProfit = array_sum(array_map(fn (VaultEntry $e) => $this->profit($e), $monthEntries));
        $monthWon = count(array_filter($monthEntries, fn (VaultEntry $e) => $this->profit($e) > 0));
        $monthCount = count($monthEntries);
        $monthWinRate = $monthCount > 0 ? ($monthWon / $monthCount) * 100 : 0.0;

        $goalProfit = $bankroll->getVaultGoalMonthlyProfit();
        $goalWinRate = $bankroll->getVaultGoalWinRate();
        $goalBetCount = $bankroll->getVaultGoalBetCount();

        return [
            'profit' => [
                'goal' => $goalProfit !== null ? (float) $goalProfit : null,
                'current' => round($monthProfit, 2),
                'percent' => $goalProfit && (float) $goalProfit > 0
                    ? min(100, round(($monthProfit / (float) $goalProfit) * 100))
                    : 0,
            ],
            'winRate' => [
                'goal' => $goalWinRate,
                'current' => round($monthWinRate, 1),
                'percent' => $goalWinRate > 0
                    ? min(100, round(($monthWinRate / $goalWinRate) * 100))
                    : 0,
            ],
            'betCount' => [
                'goal' => $goalBetCount,
                'current' => $monthCount,
                'percent' => $goalBetCount > 0
                    ? min(100, round(($monthCount / $goalBetCount) * 100))
                    : 0,
            ],
        ];
    }
}
