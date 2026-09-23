<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BankrollRepository;
use App\Repository\PronosticRepository;
use App\Repository\VaultEntryRepository;
use App\Service\VaultStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        #[CurrentUser] User $user,
        PronosticRepository $pronosticRepository,
        BankrollRepository $bankrollRepository,
        VaultEntryRepository $vaultEntryRepository,
        VaultStatsService $vaultStatsService
    ): Response {
        $vaultSummary = null;

        if ($user->hasVaultAccess()) {
            $vaultEntries = $vaultEntryRepository->findAllForBankrolls($bankrollRepository->findAllForUser($user));
            $vaultSummary = $vaultStatsService->getSummary($vaultEntries);
            $vaultEvolution = $vaultStatsService->getBalanceEvolution($vaultEntries, 30);
        }

        $recentPronostics = $pronosticRepository->findFilteredPaginated(page: 1, limit: 5)['items'];

        $daysRemaining = null;
        $periodPercent = null;
        $periodEnd = $user->getSubscriptionCurrentPeriodEnd();

        if ($periodEnd) {
            $daysRemaining = max(0, (new \DateTime())->diff($periodEnd)->days);
            $periodPercent = max(0, min(100, (int) round(($daysRemaining / 30) * 100)));
        }

        return $this->render('dashboard/index.html.twig', [
            'recentPronostics' => $recentPronostics,
            'vaultSummary' => $vaultSummary,
            'vaultEvolution' => $vaultEvolution ?? [],
            'subscriptionDaysRemaining' => $daysRemaining,
            'subscriptionPeriodPercent' => $periodPercent,
        ]);
    }
}
