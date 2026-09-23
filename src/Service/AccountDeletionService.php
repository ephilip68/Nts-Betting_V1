<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suppression réelle et complète d'un compte : abonnement Stripe annulé,
 * NTS Vault + demandes de réinitialisation supprimés, contenu communautaire
 * (posts/commentaires/likes, y compris ceux des autres membres sur ses
 * propres posts) supprimé en cascade, puis le compte lui-même. Utilisé par
 * la suppression depuis Mon profil et depuis l'admin (Utilisateurs).
 */
class AccountDeletionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StripeSubscriptionService $stripeSubscriptionService,
    ) {
    }

    public function delete(User $user): void
    {
        $this->stripeSubscriptionService->cancelSubscriptionImmediately($user);

        $projectDir = dirname(__DIR__, 2);

        $filesToDelete = [];
        if ($user->getPhoto()) {
            $filesToDelete[] = $projectDir . '/public' . $user->getPhoto();
        }
        $postPhotos = $this->entityManager->createQuery(
            'SELECT p.photo FROM App\Entity\CommunityPost p WHERE p.user = :user AND p.photo IS NOT NULL'
        )->setParameter('user', $user)->getSingleColumnResult();
        foreach ($postPhotos as $photo) {
            $filesToDelete[] = $projectDir . '/public' . $photo;
        }

        $this->entityManager->createQuery(
            'DELETE FROM App\Entity\CommunityLike l
             WHERE l.user = :user OR l.communityPost IN (SELECT p FROM App\Entity\CommunityPost p WHERE p.user = :user)'
        )->setParameter('user', $user)->execute();

        $this->entityManager->createQuery(
            'DELETE FROM App\Entity\CommunityComment c
             WHERE c.user = :user OR c.communityPost IN (SELECT p FROM App\Entity\CommunityPost p WHERE p.user = :user)'
        )->setParameter('user', $user)->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\CommunityPost p WHERE p.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\VaultEntry v WHERE v.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\PronosticFavorite f WHERE f.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r WHERE r.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        foreach ($filesToDelete as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
