<?php

namespace App\Controller\Admin;

use App\Repository\PronosticRepository;
use App\Repository\UserRepository;
use App\Service\ResultsStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistiques')]
#[IsGranted('ROLE_ADMIN')]
final class StatsController extends AbstractController
{
    private const PLAN_PRICES = [
        'starter' => 14.99,
        'essentiel' => 29.99,
        'avance' => 49.99,
        'vip' => 99.99,
    ];

    #[Route('', name: 'admin_stats_index', methods: ['GET'])]
    public function index(
        PronosticRepository $pronosticRepository,
        UserRepository $userRepository,
        ResultsStatsService $resultsStatsService
    ): Response {
        $settled = $pronosticRepository->findAllSettled();

        $activeSubscribers = $userRepository->createQueryBuilder('u')
            ->andWhere('u.subscriptionStatus = :active')
            ->setParameter('active', 'active')
            ->getQuery()
            ->getResult();

        $byPlan = [];
        $mrr = 0.0;
        foreach ($activeSubscribers as $subscriber) {
            $plan = $subscriber->getSubscriptionPlan();
            $byPlan[$plan] = ($byPlan[$plan] ?? 0) + 1;
            $mrr += self::PLAN_PRICES[$plan] ?? 0;
        }

        return $this->render('admin/stats/index.html.twig', [
            'stats' => $resultsStatsService->getGlobalStats($settled),
            'sportDistribution' => $resultsStatsService->getSportDistribution($settled),
            'monthlyPerformance' => $resultsStatsService->getMonthlyPerformance($settled, 6),
            'byPlan' => $byPlan,
            'byPlanTotal' => array_sum($byPlan),
            'mrr' => round($mrr, 2),
            'totalUsers' => $userRepository->count([]),
            'freeUsers' => $userRepository->count([]) - count($activeSubscribers),
        ]);
    }
}
