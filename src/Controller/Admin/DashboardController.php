<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\PronosticRepository;
use App\Repository\UserRepository;
use App\Service\ResultsStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __invoke(
        UserRepository $userRepository,
        PronosticRepository $pronosticRepository,
        ArticleRepository $articleRepository,
        ResultsStatsService $resultsStatsService
    ): Response {
        $settled = $pronosticRepository->findAllSettled();
        $stats = $resultsStatsService->getGlobalStats($settled);

        return $this->render('admin/dashboard/index.html.twig', [
            'usersCount' => $userRepository->count([]),
            'activeSubscribersCount' => $userRepository->count(['subscriptionStatus' => 'active']),
            'pronosticsCount' => $pronosticRepository->count([]),
            'articlesCount' => $articleRepository->count(['status' => 'published']),
            'winRate' => $stats['winRate'],
            'recentPronostics' => $pronosticRepository->findBy([], ['matchDate' => 'DESC'], 5),
            'recentArticles' => $articleRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'recentUsers' => $userRepository->findBy([], ['dateInscription' => 'DESC'], 5),
        ]);
    }
}
