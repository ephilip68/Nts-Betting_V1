<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Affiche une cote selon la préférence du membre connecté (décimal ou
 * fractionnaire), réglée dans Mon profil > Préférences.
 */
class OddsFormatExtension extends AbstractExtension
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('odds', [$this, 'formatOdds']),
        ];
    }

    public function formatOdds(string|float $odds): string
    {
        $decimal = (float) $odds;
        $user = $this->security->getUser();

        if (!$user instanceof User || $user->getOddsFormat() !== 'fractional') {
            return number_format($decimal, 2);
        }

        return $this->toFraction($decimal);
    }

    private function toFraction(float $decimal): string
    {
        $numerator = (int) round(($decimal - 1) * 100);
        $denominator = 100;

        if ($numerator <= 0) {
            return number_format($decimal, 2);
        }

        $divisor = $this->gcd($numerator, $denominator);

        return sprintf('%d/%d', $numerator / $divisor, $denominator / $divisor);
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }
}
