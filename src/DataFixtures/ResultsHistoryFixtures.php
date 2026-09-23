<?php

namespace App\DataFixtures;

use App\Entity\Pronostic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Historique de pronostics déjà joués (gagnés/perdus), pour peupler la page
 * publique /resultats (stats, graphiques, derniers résultats) avec des
 * données réalistes. Séparée d'AppFixtures pour pouvoir être ajoutée sans
 * purger les comptes existants : `doctrine:fixtures:load --append --group=results-history`.
 */
class ResultsHistoryFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['results-history'];
    }

    private const MATCHES = [
        ['football', 'Ligue 1', 'PSG', 'Marseille', 'psg.png', null, '1X2', 'PSG', 1.35],
        ['football', 'Ligue 1', 'Monaco', 'Lyon', null, null, 'Double chance', 'Monaco ou nul', 1.42],
        ['football', 'Premier League', 'Arsenal', 'Liverpool', 'arsenal.png', 'liverpool.png', '1X2', 'Liverpool', 2.10],
        ['football', 'Premier League', 'Manchester City', 'Manchester United', 'city.png', 'united.png', '1X2', 'Manchester City', 1.55],
        ['football', 'Liga', 'Real Madrid', 'Atletico Madrid', 'real-madrid.png', null, '1X2', 'Real Madrid', 1.75],
        ['football', 'Liga', 'Barcelone', 'Séville', 'barcelone.png', null, 'Plus de 2.5 buts', 'Oui', 1.68],
        ['football', 'Serie A', 'Inter Milan', 'AC Milan', 'inter.png', 'milan.png', '1X2', 'Inter Milan', 2.05],
        ['football', 'Bundesliga', 'Bayern Munich', 'Dortmund', 'bayern.png', 'dortmund.png', '1X2', 'Bayern Munich', 1.48],
        ['football', 'Ligue des Champions', 'PSG', 'Dortmund', 'psg.png', 'dortmund.png', 'Les deux équipes marquent', 'Oui', 1.80],
        ['football', 'Ligue des Champions', 'Real Madrid', 'Manchester City', 'real-madrid.png', 'city.png', '1X2', 'Manchester City', 2.30],
        ['basketball', 'NBA', 'Boston Celtics', 'Miami Heat', 'boston.png', 'miami.png', 'Handicap', 'Boston Celtics -4.5', 1.90],
        ['basketball', 'NBA', 'Lakers', 'Celtics', null, 'boston.png', 'Total points', 'Plus de 215.5', 1.87],
        ['tennis', 'ATP Paris', 'Djokovic', 'Sinner', null, null, 'Vainqueur du match', 'Djokovic', 1.65],
        ['tennis', 'WTA Pékin', 'Swiatek', 'Sabalenka', null, null, 'Vainqueur du match', 'Swiatek', 1.72],
    ];

    public function load(ObjectManager $manager): void
    {
        mt_srand(42);

        $count = 38;
        $date = (new \DateTime())->modify('-2 days');

        for ($i = 0; $i < $count; $i++) {
            $template = self::MATCHES[$i % count(self::MATCHES)];
            [$sport, $competition, $teamHome, $teamAway, $logoHome, $logoAway, $betType, $betValue, $odds] = $template;

            // Petite variation de cote pour ne pas répéter exactement les mêmes valeurs
            $odds = round($odds + (mt_rand(-8, 8) / 100), 2);
            $odds = max(1.10, $odds);

            $won = (mt_rand(1, 100) <= 68);

            $confidence = match (true) {
                $odds <= 1.45 => 5,
                $odds <= 1.65 => 4,
                $odds <= 1.90 => 3,
                $odds <= 2.20 => 2,
                default => 1,
            };
            $stake = match ($confidence) {
                5, 4 => '30.00',
                3 => '20.00',
                default => '10.00',
            };

            $pronostic = new Pronostic();
            $pronostic->setSport($sport);
            $pronostic->setCompetition($competition);
            $pronostic->setTeamHome($teamHome);
            $pronostic->setTeamAway($teamAway);
            $pronostic->setTeamHomeLogo($logoHome ? '/images/resultats/teams/' . $logoHome : null);
            $pronostic->setTeamAwayLogo($logoAway ? '/images/resultats/teams/' . $logoAway : null);
            $pronostic->setMatchDate((clone $date)->setTime(random_int(13, 21), [0, 15, 30, 45][random_int(0, 3)]));
            $pronostic->setBetType($betType);
            $pronostic->setBetValue($betValue);
            $pronostic->setOdds((string) $odds);
            $pronostic->setStake($stake);
            $pronostic->setConfidence($confidence);
            $pronostic->setIsVip($i % 3 === 0);
            $pronostic->setStatus($won ? Pronostic::STATUS_WON : Pronostic::STATUS_LOST);
            $manager->persist($pronostic);

            // Le pronostic précédent (chronologiquement) a été publié 1 à 3 jours avant
            $date->modify('-' . random_int(1, 3) . ' days');
        }

        $manager->flush();
    }
}
