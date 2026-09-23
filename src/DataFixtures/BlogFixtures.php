<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Quelques articles de démonstration pour le blog, chargés en --append
 * (n'affecte pas les comptes/données existants).
 */
class BlogFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['blog-history'];
    }

    public function load(ObjectManager $manager): void
    {
        $article1 = new Article();
        $article1->setTitle('Ligue des Champions 2024/25 : quelles sont les vraies opportunités ?');
        $article1->setSlug('ligue-des-champions-2024-25-opportunites');
        $article1->setExcerpt("Analyse approfondie des équipes en forme, des cotes sous-évaluées et des stratégies pour profiter pleinement de la plus prestigieuse des compétitions européennes.");
        $article1->setContent(<<<MD
La Ligue des Champions 2024/25 promet encore une fois un spectacle exceptionnel avec les plus grands clubs européens. Mais au-delà du spectacle, elle offre également de nombreuses opportunités pour les parieurs attentifs.

## Les équipes en forme

Certaines équipes se démarquent déjà depuis le début de la compétition. Le Real Madrid, Manchester City et le Bayern Munich affichent une régularité impressionnante. Mais d'autres clubs, comme l'Inter Milan ou le RB Leipzig, pourraient créer la surprise.

> Les meilleures opportunités ne se trouvent souvent pas là où le grand public ne regarde pas.

## Les cotes sous-évaluées

En analysant les performances récentes et les statistiques avancées, nous avons identifié plusieurs matchs où les cotes proposées par les bookmakers semblent sous-évaluées. C'est notamment le cas pour certaines équipes qui affichent de bons résultats à domicile mais restent médiatisées.

## Notre analyse

Sur la base de ces éléments, notre conviction est claire : la valeur se trouve dans les équipes solides à domicile, avec une défense organisée et un style de jeu maîtrisé plutôt que spectaculaire.
MD);
        $article1->setCategory('football');
        $article1->setAuthor('NTS Team');
        $article1->setIsFeatured(true);
        $article1->setStatus('published');
        $article1->setPublishedAt((new \DateTime())->modify('-10 days'));
        $manager->persist($article1);

        $article2 = new Article();
        $article2->setTitle('Comment bien gérer sa bankroll en 2024 ?');
        $article2->setSlug('gerer-sa-bankroll-2024');
        $article2->setExcerpt('Les principes de base de la gestion de bankroll, pour parier de façon responsable et durable sur le long terme.');
        $article2->setContent(<<<MD
La gestion de bankroll est probablement l'aspect le plus sous-estimé par les parieurs débutants — et pourtant l'un des plus déterminants sur la durée.

## Définir son unité de mise

La première étape consiste à définir une unité de mise fixe, généralement entre 1% et 3% de sa bankroll totale, et à ne jamais s'en écarter, même après une série de pertes.

## Adapter la mise à la confiance

Une mise plus élevée peut se justifier sur un pronostic à forte confiance, mais elle ne devrait jamais dépasser 2 à 3 unités, même dans les meilleures configurations.

## Accepter la variance

Aucune méthode, aussi rigoureuse soit-elle, n'élimine la variance à court terme. C'est la discipline sur la durée qui fait la différence, pas un coup ponctuel.
MD);
        $article2->setCategory('strategies');
        $article2->setAuthor('NTS Team');
        $article2->setIsFeatured(true);
        $article2->setStatus('published');
        $article2->setPublishedAt((new \DateTime())->modify('-14 days'));
        $manager->persist($article2);

        $article3 = new Article();
        $article3->setTitle('Les 5 erreurs que font 90% des parieurs débutants');
        $article3->setSlug('5-erreurs-parieurs-debutants');
        $article3->setExcerpt('Les pièges les plus courants à éviter quand on commence à parier sérieusement.');
        $article3->setContent(<<<MD
Avant de chercher à optimiser ses gains, il est essentiel d'éviter les erreurs qui plombent la plupart des parcours de parieurs débutants.

## Parier sous le coup de l'émotion

Miser après une victoire de son équipe favorite, ou pour "se refaire" après une perte, sont deux réflexes qui mènent presque toujours à des décisions irrationnelles.

## Ignorer la gestion de bankroll

Sans plan de mise clair, même une stratégie de pronostics rentable à long terme peut se solder par une bankroll épuisée après une simple mauvaise série.

## Multiplier les combinés à fort risque

Les combinés à cotes élevées sont statistiquement les paris les moins rentables sur la durée, malgré leur attrait apparent.
MD);
        $article3->setCategory('strategies');
        $article3->setAuthor('NTS Team');
        $article3->setStatus('published');
        $article3->setPublishedAt((new \DateTime())->modify('-20 days'));
        $manager->persist($article3);

        $manager->flush();
    }
}
