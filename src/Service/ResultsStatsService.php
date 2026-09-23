<?php

namespace App\Service;

use App\Entity\Pronostic;

/**
 * Calcule les statistiques de la page publique /resultats (taux de réussite,
 * gains en euros, ROI, séries, performance mensuelle...) à partir des
 * pronostics déjà joués (gagné/perdu). Calculé sur la mise nominale de chaque
 * pronostic (Pronostic::$stake) : indicatif, pas le solde réel d'un utilisateur.
 */
class ResultsStatsService
{
    private const SPORT_LABELS = [
        'football' => 'Football',
        'basketball' => 'Basketball',
        'tennis' => 'Tennis',
        'baseball' => 'Sports US',
    ];

    private const SPORT_ICONS = [
        'football' => 'fa-futbol',
        'basketball' => 'fa-basketball',
        'tennis' => 'fa-table-tennis-paddle-ball',
        'baseball' => 'fa-baseball',
    ];

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juil', 8 => 'Aoû', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
    ];

    /**
     * Profit/perte d'un pronostic déjà joué, en euros (mise nominale).
     */
    public function profit(Pronostic $pronostic): float
    {
        $stake = (float) $pronostic->getStake();

        if ($pronostic->getStatus() === Pronostic::STATUS_WON) {
            return $stake * ((float) $pronostic->getOdds() - 1);
        }

        return -$stake;
    }

    /**
     * @param Pronostic[] $settled Pronostics gagné/perdu, triés par date croissante
     */
    public function getGlobalStats(array $settled): array
    {
        $count = count($settled);

        if ($count === 0) {
            return [
                'settledCount' => 0,
                'winRate' => null,
                'unitsProfit' => 0.0,
                'avgOdds' => null,
                'avgStake' => null,
                'roi' => null,
                'longestWinStreak' => 0,
                'longestLossStreak' => 0,
                'bestMonth' => null,
            ];
        }

        $wins = 0;
        $unitsProfit = 0.0;
        $totalStaked = 0.0;
        $totalOdds = 0.0;
        $totalStake = 0.0;

        $currentStreak = 0;
        $currentStreakType = null;
        $longestWinStreak = 0;
        $longestLossStreak = 0;

        $monthlyProfit = [];

        foreach ($settled as $pronostic) {
            $won = $pronostic->getStatus() === Pronostic::STATUS_WON;
            $profit = $this->profit($pronostic);
            $stake = (float) $pronostic->getStake();

            $wins += $won ? 1 : 0;
            $unitsProfit += $profit;
            $totalStaked += $stake;
            $totalOdds += (float) $pronostic->getOdds();
            $totalStake += $stake;

            if ($currentStreakType === $won) {
                $currentStreak++;
            } else {
                $currentStreakType = $won;
                $currentStreak = 1;
            }

            if ($won) {
                $longestWinStreak = max($longestWinStreak, $currentStreak);
            } else {
                $longestLossStreak = max($longestLossStreak, $currentStreak);
            }

            $monthKey = $pronostic->getMatchDate()->format('Y-m');
            $monthlyProfit[$monthKey] = ($monthlyProfit[$monthKey] ?? 0) + $profit;
        }

        $bestMonth = null;
        if ($monthlyProfit !== []) {
            $bestMonthKey = array_search(max($monthlyProfit), $monthlyProfit, true);
            [$year, $month] = explode('-', $bestMonthKey);
            $bestMonth = [
                'label' => self::MONTH_LABELS[(int) $month] . ' ' . $year,
                'units' => $monthlyProfit[$bestMonthKey],
            ];
        }

        return [
            'settledCount' => $count,
            'winRate' => round(($wins / $count) * 100, 1),
            'unitsProfit' => round($unitsProfit, 2),
            'avgOdds' => round($totalOdds / $count, 2),
            'avgStake' => round($totalStake / $count, 2),
            'roi' => $totalStaked > 0 ? round(($unitsProfit / $totalStaked) * 100, 1) : null,
            'longestWinStreak' => $longestWinStreak,
            'longestLossStreak' => $longestLossStreak,
            'bestMonth' => $bestMonth,
        ];
    }

    /**
     * Performance des `$months` derniers mois (glissant, mois courant inclus),
     * pour le graphique. Les mois sans pronostic joué sont à zéro.
     *
     * @param Pronostic[] $settled
     */
    public function getMonthlyPerformance(array $settled, int $months = 12): array
    {
        $byMonth = [];
        foreach ($settled as $pronostic) {
            $key = $pronostic->getMatchDate()->format('Y-m');
            $byMonth[$key]['won'] ??= 0.0;
            $byMonth[$key]['count'] ??= 0;
            $byMonth[$key]['wins'] ??= 0;

            $won = $pronostic->getStatus() === Pronostic::STATUS_WON;
            $profit = $this->profit($pronostic);

            $byMonth[$key]['won'] += $profit > 0 ? $profit : 0.0;
            $byMonth[$key]['count']++;
            $byMonth[$key]['wins'] += $won ? 1 : 0;
        }

        $result = [];
        $cursor = new \DateTime('first day of this month');

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthDate = (clone $cursor)->modify("-{$i} months");
            $key = $monthDate->format('Y-m');
            $data = $byMonth[$key] ?? ['won' => 0.0, 'count' => 0, 'wins' => 0];

            $result[] = [
                'label' => self::MONTH_LABELS[(int) $monthDate->format('n')],
                'unitsWon' => round($data['won'], 2),
                'winRate' => $data['count'] > 0 ? round(($data['wins'] / $data['count']) * 100, 1) : 0.0,
                'count' => $data['count'],
            ];
        }

        return $result;
    }

    /**
     * Répartition des pronostics joués par sport (pour le donut).
     *
     * @param Pronostic[] $settled
     */
    public function getSportDistribution(array $settled): array
    {
        $count = count($settled);
        if ($count === 0) {
            return [];
        }

        $bySport = [];
        foreach ($settled as $pronostic) {
            $bySport[$pronostic->getSport()] = ($bySport[$pronostic->getSport()] ?? 0) + 1;
        }
        arsort($bySport);

        $result = [];
        foreach ($bySport as $sport => $sportCount) {
            $result[] = [
                'sport' => $sport,
                'label' => self::SPORT_LABELS[$sport] ?? ucfirst($sport),
                'icon' => self::SPORT_ICONS[$sport] ?? 'fa-circle',
                'count' => $sportCount,
                'percent' => round(($sportCount / $count) * 100, 1),
            ];
        }

        return $result;
    }
}
