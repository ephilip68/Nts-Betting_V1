<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Reçoit les événements Stripe. Cette route doit rester publique (pas de session,
 * pas de CSRF) : Stripe l'appelle directement depuis ses serveurs. La sécurité
 * repose sur la vérification de signature ci-dessous, pas sur l'authentification.
 */
final class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        string $stripeWebhookSecret,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        LoggerInterface $logger
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $stripeWebhookSecret);
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            $logger->warning('Signature webhook Stripe invalide.', ['error' => $e->getMessage()]);

            return new Response('Signature invalide', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event, $entityManager, $userRepository),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($event, $entityManager, $userRepository),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($event, $entityManager, $userRepository),
            default => null,
        };

        return new Response('OK', 200);
    }

    private function onCheckoutCompleted(Event $event, EntityManagerInterface $entityManager, UserRepository $userRepository): void
    {
        $session = $event->data->object;
        $userId = $session->metadata->user_id ?? null;
        $plan = $session->metadata->plan ?? null;

        if (!$userId) {
            return;
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            return;
        }

        $user->setStripeSubscriptionId($session->subscription ?? null);
        $user->setSubscriptionPlan($plan);
        $user->setSubscriptionStatus('active');

        $entityManager->flush();
    }

    private function onSubscriptionUpdated(Event $event, EntityManagerInterface $entityManager, UserRepository $userRepository): void
    {
        $subscription = $event->data->object;
        $user = $userRepository->findOneBy(['stripeSubscriptionId' => $subscription->id]);

        if (!$user) {
            return;
        }

        $user->setSubscriptionStatus($subscription->status);

        $periodEnd = $subscription->current_period_end ?? null;
        if ($periodEnd) {
            $user->setSubscriptionCurrentPeriodEnd((new \DateTime())->setTimestamp($periodEnd));
        }

        $plan = $subscription->metadata->plan ?? null;
        if ($plan) {
            $user->setSubscriptionPlan($plan);
        }

        $entityManager->flush();
    }

    private function onSubscriptionDeleted(Event $event, EntityManagerInterface $entityManager, UserRepository $userRepository): void
    {
        $subscription = $event->data->object;
        $user = $userRepository->findOneBy(['stripeSubscriptionId' => $subscription->id]);

        if (!$user) {
            return;
        }

        $user->setSubscriptionStatus('canceled');
        $user->setSubscriptionPlan(null);

        $entityManager->flush();
    }
}
