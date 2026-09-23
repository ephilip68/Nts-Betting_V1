<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class StripeSubscriptionService
{
    private StripeClient $stripe;

    /**
     * @param array<string, string> $planPrices Slug d'offre => ID de Price Stripe
     */
    public function __construct(
        string $stripeSecretKey,
        private readonly array $planPrices,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
    ) {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    public function getPriceIdForPlan(string $plan): ?string
    {
        return $this->planPrices[$plan] ?? null;
    }

    /**
     * Récupère le Customer Stripe de l'utilisateur, ou en crée un s'il n'en a pas encore.
     */
    public function getOrCreateCustomer(User $user): string
    {
        if ($user->getStripeCustomerId()) {
            return $user->getStripeCustomerId();
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->getEmail(),
            'name' => $user->getNickname(),
            'metadata' => [
                'user_id' => $user->getId(),
            ],
        ]);

        $user->setStripeCustomerId($customer->id);
        $this->entityManager->flush();

        return $customer->id;
    }

    /**
     * Crée une session Stripe Checkout (mode abonnement) pour un plan donné et renvoie son URL.
     */
    public function createCheckoutSessionUrl(User $user, string $plan): string
    {
        $priceId = $this->getPriceIdForPlan($plan);

        if (!$priceId) {
            throw new \InvalidArgumentException(sprintf('Aucun price Stripe configuré pour le plan "%s".', $plan));
        }

        $customerId = $this->getOrCreateCustomer($user);

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $this->router->generate('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->router->generate('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'user_id' => $user->getId(),
                'plan' => $plan,
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => $user->getId(),
                    'plan' => $plan,
                ],
            ],
            'allow_promotion_codes' => true,
        ]);

        return $session->url;
    }

    /**
     * Crée une session du "Customer Portal" Stripe (gestion de l'abonnement, factures, moyen de paiement).
     */
    public function createBillingPortalSessionUrl(User $user): string
    {
        $customerId = $this->getOrCreateCustomer($user);

        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $this->router->generate('app_profile', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $session->url;
    }

    public function getClient(): StripeClient
    {
        return $this->stripe;
    }

    /**
     * Historique de facturation du membre (pour l'onglet "Mes paiements" du profil).
     *
     * @return array<int, array{date: \DateTimeImmutable, description: string, amount: float, status: string, invoiceUrl: ?string}>
     */
    public function listInvoices(User $user, int $limit = 12): array
    {
        if (!$user->getStripeCustomerId()) {
            return [];
        }

        $invoices = $this->stripe->invoices->all([
            'customer' => $user->getStripeCustomerId(),
            'limit' => $limit,
        ]);

        return array_map(static function ($invoice) {
            return [
                'date' => (new \DateTimeImmutable())->setTimestamp($invoice->created),
                'description' => $invoice->lines->data[0]->description ?? 'Abonnement NTS Betting',
                'amount' => $invoice->amount_paid / 100,
                'status' => $invoice->status,
                'invoiceUrl' => $invoice->hosted_invoice_url ?? null,
            ];
        }, $invoices->data);
    }

    /**
     * Annule immédiatement l'abonnement Stripe du membre (utilisé à la suppression du compte).
     */
    public function cancelSubscriptionImmediately(User $user): void
    {
        if (!$user->getStripeSubscriptionId()) {
            return;
        }

        try {
            $this->stripe->subscriptions->cancel($user->getStripeSubscriptionId());
        } catch (\Throwable) {
            // L'abonnement est peut-être déjà annulé côté Stripe : on ignore, la suppression du compte doit continuer.
        }
    }
}
