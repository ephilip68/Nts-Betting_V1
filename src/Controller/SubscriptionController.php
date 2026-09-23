<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StripeSubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SubscriptionController extends AbstractController
{
    private const VALID_PLANS = ['starter', 'essentiel', 'avance', 'vip'];

    #[Route('/abonnement/checkout/{plan}', name: 'app_subscription_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(
        string $plan,
        #[CurrentUser] User $user,
        StripeSubscriptionService $stripeSubscriptionService
    ): RedirectResponse {
        if (!in_array($plan, self::VALID_PLANS, true)) {
            throw $this->createNotFoundException('Offre inconnue.');
        }

        try {
            $checkoutUrl = $stripeSubscriptionService->createCheckoutSessionUrl($user, $plan);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible de lancer le paiement pour le moment. Réessaie dans quelques instants.');

            return $this->redirectToRoute('app_offers');
        }

        return $this->redirect($checkoutUrl);
    }

    #[Route('/abonnement/portail', name: 'app_subscription_portal', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function portal(
        #[CurrentUser] User $user,
        StripeSubscriptionService $stripeSubscriptionService
    ): RedirectResponse {
        try {
            $portalUrl = $stripeSubscriptionService->createBillingPortalSessionUrl($user);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'ouvrir la gestion de l\'abonnement pour le moment.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->redirect($portalUrl);
    }

    #[Route('/abonnement/succes', name: 'app_subscription_success', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        // Le VIP réel est activé via le webhook Stripe (checkout.session.completed),
        // qui peut arriver quelques secondes après le retour de l'utilisateur ici.
        return $this->render('subscription/success.html.twig');
    }

    #[Route('/abonnement/annule', name: 'app_subscription_cancel', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        return $this->render('subscription/cancel.html.twig');
    }
}
