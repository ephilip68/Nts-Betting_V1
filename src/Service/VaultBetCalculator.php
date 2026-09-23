<?php

namespace App\Service;

use App\Entity\VaultEntry;
use App\Entity\VaultEntrySelection;

/**
 * Calcule le résultat (statut, cote finale, profit) d'un pari du NTS Vault,
 * quel que soit son type : simple/live, combiné/live combiné (avec option
 * boost et assurance), ou système (Trixie, Patent, "N/M"...).
 *
 * Toujours exécuté côté serveur à l'enregistrement — les calculs équivalents
 * côté client (assets/js/vault-bet-form.js) ne servent qu'à l'aperçu live et
 * ne sont jamais la source de vérité du profit persisté.
 */
final class VaultBetCalculator
{
    /** Bonus de cote appliqué à chaque sélection quand "Cote boostée" est actif, par palier de cote. */
    private const BOOST_TIERS = [
        1.5 => 0.003,
        2 => 0.0062,
        3 => 0.008,
        5 => 0.018,
        7 => 0.020,
        10 => 0.025,
    ];
    private const BOOST_DEFAULT = 0.045;

    /** Réduction de cote appliquée quand "Assurer mon combiné" est actif et qu'aucune sélection n'a perdu. */
    private const INSURANCE_REDUCTION = [
        2 => 0.15,
        3 => 0.25,
        4 => 0.363,
        5 => 0.2405,
        6 => 0.1705,
        7 => 0.11,
        8 => 0.07305,
    ];
    private const INSURANCE_DEFAULT = 0.0465;

    /** Nombre de sélections composant chaque pari élémentaire d'un système nommé. */
    private const NAMED_SYSTEM_LEGS = [
        'Trixie' => [2, 3],
        'Patent' => [1, 2, 3],
        'Yankee' => [2, 3, 4],
        'Lucky 15' => [1, 2, 3, 4],
        'Canadian (Super Yankee)' => [2, 3, 4, 5],
        'Lucky 31' => [1, 2, 3, 4, 5],
        'Heinz' => [2, 3, 4, 5, 6],
        'Lucky 63' => [1, 2, 3, 4, 5, 6],
        'Super Heinz' => [2, 3, 4, 5, 6, 7],
        'Goliath' => [2, 3, 4, 5, 6, 7, 8],
    ];

    public function calculate(VaultEntry $entry): void
    {
        if ($entry->isSysteme()) {
            $this->calculateSysteme($entry);

            return;
        }

        if ($entry->isCombined()) {
            $this->calculateCombined($entry);

            return;
        }

        $this->calculateSimple($entry);
    }

    private function calculateSimple(VaultEntry $entry): void
    {
        $stake = (float) $entry->getStake();

        switch ($entry->getStatus()) {
            case VaultEntry::STATUS_WON:
                $profit = round($stake * ((float) $entry->getOdds() - 1), 2);
                break;

            case VaultEntry::STATUS_LOST:
                $profit = $entry->isFreebet() ? 0.0 : round(-$stake, 2);
                break;

            case VaultEntry::STATUS_CASHOUT:
                $gain = $entry->getCashoutGain();
                $profit = $gain !== null ? round((float) $gain - $stake, 2) : null;
                break;

            default:
                $profit = null;
        }

        $entry->setProfit($profit !== null ? (string) $profit : null);
    }

    private function calculateCombined(VaultEntry $entry): void
    {
        $selections = $entry->getSelections();
        $count = $selections->count();

        if ($count === 0) {
            $entry->setOdds('1.00');
            $entry->setProfit(null);
            $entry->setStatus(VaultEntry::STATUS_PENDING);

            return;
        }

        $lostCount = 0;
        $pendingCount = 0;
        $boostedOdds = [];

        foreach ($selections as $selection) {
            $odd = (float) $selection->getOdds();

            if ($entry->isBoosted() && $count >= 2) {
                $odd = $this->boostOdd($odd, $count);
            }

            $boostedOdds[] = round($odd, 2);

            if ($selection->getField() === VaultEntrySelection::FIELD_LOSE) {
                $lostCount++;
            } elseif ($selection->getField() === VaultEntrySelection::FIELD_PENDING) {
                $pendingCount++;
            }
        }

        $total = round(array_product($boostedOdds), 2);

        if ($entry->isInsured() && $lostCount === 0 && $pendingCount === 0) {
            $total = round($total * (1 - $this->insuranceReduction($count)), 2);
        }

        $entry->setOdds((string) $total);

        $stake = (float) $entry->getStake();

        if ($pendingCount > 0) {
            $entry->setStatus(VaultEntry::STATUS_PENDING);
            $entry->setProfit(null);

            return;
        }

        if ($lostCount === 0) {
            $entry->setStatus(VaultEntry::STATUS_WON);
            $entry->setProfit((string) round($stake * $total - $stake, 2));

            return;
        }

        if ($lostCount === 1 && $entry->isInsured()) {
            // Assurance : une seule sélection perdante => mise remboursée, ni gain ni perte.
            $entry->setStatus(VaultEntry::STATUS_LOST);
            $entry->setProfit('0.00');

            return;
        }

        $entry->setStatus(VaultEntry::STATUS_LOST);
        $entry->setProfit($entry->isFreebet() ? '0.00' : (string) round(-$stake, 2));
    }

    private function calculateSysteme(VaultEntry $entry): void
    {
        $selections = $entry->getSelections();
        $systemOption = $entry->getSystemOption();
        $stake = (float) $entry->getStake();

        if ($selections->isEmpty() || !$systemOption) {
            $entry->setProfit(null);
            $entry->setStatus(VaultEntry::STATUS_PENDING);

            return;
        }

        foreach ($selections as $selection) {
            if ($selection->getField() === VaultEntrySelection::FIELD_PENDING) {
                $entry->setProfit(null);
                $entry->setStatus(VaultEntry::STATUS_PENDING);

                return;
            }
        }

        $selectionList = array_values($selections->toArray());
        $legSizes = $this->systemLegSizes($systemOption->getLabel());

        $totalCombos = 0;
        $winningPayout = 0.0;

        foreach ($legSizes as $legSize) {
            foreach ($this->combinationsOfIndexes(count($selectionList), $legSize) as $combo) {
                $totalCombos++;
                $allWin = true;
                $product = 1.0;

                foreach ($combo as $i) {
                    if ($selectionList[$i]->getField() !== VaultEntrySelection::FIELD_WIN) {
                        $allWin = false;
                    }
                    $product *= (float) $selectionList[$i]->getOdds();
                }

                if ($allWin) {
                    $winningPayout += round($product, 2);
                }
            }
        }

        if ($totalCombos === 0) {
            $entry->setProfit(null);
            $entry->setStatus(VaultEntry::STATUS_PENDING);

            return;
        }

        $unitStake = $stake / $totalCombos;
        $payout = round($unitStake * $winningPayout, 2);
        $profit = round($payout - $stake, 2);

        // Cote moyenne informative (un système n'a pas de cote unique, contrairement à un combiné).
        $entry->setOdds((string) round($stake > 0 ? ($payout / $stake) : 1, 2));
        $entry->setProfit((string) $profit);

        if ($profit > 0) {
            $entry->setStatus(VaultEntry::STATUS_WON);
        } elseif ($profit < 0) {
            $entry->setStatus($entry->isFreebet() ? VaultEntry::STATUS_VOID : VaultEntry::STATUS_LOST);
            if ($entry->isFreebet()) {
                $entry->setProfit('0.00');
            }
        } else {
            $entry->setStatus(VaultEntry::STATUS_VOID);
        }
    }

    private function boostOdd(float $odd, int $selectionCount): float
    {
        $boostPercent = self::BOOST_DEFAULT;

        foreach (self::BOOST_TIERS as $threshold => $percent) {
            if ($odd < $threshold) {
                $boostPercent = $percent;
                break;
            }
        }

        $matchBonus = $selectionCount > 2 ? min(($selectionCount - 2) * 0.0015, 0.006) : 0.0;

        return $odd * (1 + $boostPercent + $matchBonus);
    }

    private function insuranceReduction(int $selectionCount): float
    {
        return self::INSURANCE_REDUCTION[$selectionCount] ?? self::INSURANCE_DEFAULT;
    }

    /**
     * @return int[] Tailles de combinaisons (nombre de sélections par pari élémentaire) pour un système donné.
     */
    private function systemLegSizes(string $label): array
    {
        if (preg_match('#^(\d+)/(\d+)$#', $label, $m)) {
            return [(int) $m[1]];
        }

        return self::NAMED_SYSTEM_LEGS[$label] ?? [];
    }

    /**
     * Toutes les combinaisons de $k indices parmi [0, $n[.
     *
     * @return array<int, int[]>
     */
    private function combinationsOfIndexes(int $n, int $k): array
    {
        if ($k <= 0 || $k > $n) {
            return [];
        }

        $result = [];
        $combo = [];

        $helper = function (int $start) use (&$helper, &$result, &$combo, $n, $k): void {
            if (count($combo) === $k) {
                $result[] = $combo;

                return;
            }

            for ($i = $start; $i < $n; $i++) {
                $combo[] = $i;
                $helper($i + 1);
                array_pop($combo);
            }
        };

        $helper(0);

        return $result;
    }
}
