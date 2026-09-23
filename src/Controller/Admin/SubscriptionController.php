<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/abonnements')]
#[IsGranted('ROLE_ADMIN')]
final class SubscriptionController extends AbstractController
{
    private const PLAN_PRICES = [
        'starter' => 14.99,
        'essentiel' => 29.99,
        'avance' => 49.99,
        'vip' => 99.99,
    ];

    #[Route('', name: 'admin_subscription_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $subscribers = $userRepository->createQueryBuilder('u')
            ->andWhere('u.subscriptionStatus IS NOT NULL')
            ->orderBy('u.subscriptionCurrentPeriodEnd', 'ASC')
            ->getQuery()
            ->getResult();

        $byPlan = [];
        $mrr = 0.0;
        $activeCount = 0;

        foreach ($subscribers as $subscriber) {
            $plan = $subscriber->getSubscriptionPlan();
            $byPlan[$plan] = ($byPlan[$plan] ?? 0) + 1;

            if ($subscriber->getSubscriptionStatus() === 'active') {
                $activeCount++;
                $mrr += self::PLAN_PRICES[$plan] ?? 0;
            }
        }

        return $this->render('admin/subscription/index.html.twig', [
            'subscribers' => $subscribers,
            'byPlan' => $byPlan,
            'mrr' => round($mrr, 2),
            'activeCount' => $activeCount,
        ]);
    }
}
