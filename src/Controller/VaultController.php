<?php

namespace App\Controller;

use App\Entity\Bankroll;
use App\Entity\User;
use App\Entity\VaultEntry;
use App\Form\VaultEntryFormType;
use App\Form\VaultGoalsFormType;
use App\Repository\BankrollRepository;
use App\Repository\VaultEntryRepository;
use App\Service\VaultBetCalculator;
use App\Service\VaultStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/nts-vault')]
final class VaultController extends AbstractController
{
    #[Route('', name: 'app_vault', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): Response
    {
        if ($user && $user->hasVaultAccess()) {
            return $this->redirectToRoute('app_vault_bankrolls');
        }

        return $this->render('vault/presentation.html.twig');
    }

    #[Route('/bankrolls', name: 'app_vault_bankrolls', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function bankrolls(
        #[CurrentUser] User $user,
        BankrollRepository $bankrollRepository,
        VaultEntryRepository $vaultEntryRepository,
        VaultStatsService $vaultStatsService
    ): Response {
        if (!$user->hasVaultAccess()) {
            return $this->render('vault/locked.html.twig');
        }

        $bankrolls = $bankrollRepository->findAllForUser($user);

        $cards = array_map(function (Bankroll $bankroll) use ($vaultEntryRepository, $vaultStatsService) {
            $summary = $vaultStatsService->getSummary($vaultEntryRepository->findAllForBankroll($bankroll));

            return ['bankroll' => $bankroll, 'summary' => $summary];
        }, $bankrolls);

        return $this->render('vault/bankrolls.html.twig', [
            'cards' => $cards,
            'maxBankrolls' => $user->getMaxBankrolls(),
            'canCreate' => count($bankrolls) < $user->getMaxBankrolls(),
        ]);
    }

    #[Route('/bankrolls/nouveau', name: 'app_vault_bankroll_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function newBankroll(
        #[CurrentUser] User $user,
        Request $request,
        BankrollRepository $bankrollRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$user->hasVaultAccess()) {
            return $this->redirectToRoute('app_vault');
        }

        if (!$this->isCsrfTokenValid('bankroll-new', $request->request->get('_token'))) {
            $this->addFlash('error', 'Action refusée, réessaie.');

            return $this->redirectToRoute('app_vault_bankrolls');
        }

        if ($bankrollRepository->countForUser($user) >= $user->getMaxBankrolls()) {
            $this->addFlash('error', 'Tu as atteint la limite de bankrolls de ton palier.');

            return $this->redirectToRoute('app_vault_bankrolls');
        }

        $name = trim((string) $request->request->get('name'));

        if ($name === '') {
            $this->addFlash('error', 'Donne un nom à ton bankroll.');

            return $this->redirectToRoute('app_vault_bankrolls');
        }

        $bankroll = new Bankroll();
        $bankroll->setUser($user);
        $bankroll->setName($name);

        $entityManager->persist($bankroll);
        $entityManager->flush();

        $this->addFlash('success', 'Bankroll créé.');

        return $this->redirectToRoute('app_vault_bankroll_detail', ['id' => $bankroll->getId()]);
    }

    #[Route('/bankrolls/{id}/modifier', name: 'app_vault_bankroll_rename', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function renameBankroll(Bankroll $bankroll, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('BANKROLL_EDIT', $bankroll);

        if (!$this->isCsrfTokenValid('bankroll-rename-' . $bankroll->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Action refusée, réessaie.');

            return $this->redirectToRoute('app_vault_bankrolls');
        }

        $name = trim((string) $request->request->get('name'));

        if ($name !== '') {
            $bankroll->setName($name);
            $entityManager->flush();
            $this->addFlash('success', 'Bankroll renommé.');
        }

        return $this->redirectToRoute('app_vault_bankrolls');
    }

    #[Route('/bankrolls/{id}/supprimer', name: 'app_vault_bankroll_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteBankroll(Bankroll $bankroll, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('BANKROLL_EDIT', $bankroll);

        if ($this->isCsrfTokenValid('bankroll-delete-' . $bankroll->getId(), $request->request->get('_token'))) {
            $entityManager->remove($bankroll);
            $entityManager->flush();
            $this->addFlash('success', 'Bankroll supprimé.');
        }

        return $this->redirectToRoute('app_vault_bankrolls');
    }

    #[Route('/bankrolls/{id}', name: 'app_vault_bankroll_detail', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function bankrollDetail(
        Bankroll $bankroll,
        VaultEntryRepository $vaultEntryRepository,
        VaultStatsService $vaultStatsService
    ): Response {
        $this->denyAccessUnlessGranted('BANKROLL_EDIT', $bankroll);

        $entries = $vaultEntryRepository->findAllForBankroll($bankroll);
        $period = 30;
        $evolution = $vaultStatsService->getBalanceEvolution($entries, $period);

        return $this->render('vault/bankroll_detail.html.twig', [
            'bankroll' => $bankroll,
            'summary' => $vaultStatsService->getSummary($entries),
            'evolution' => $evolution,
            'chart' => $this->buildBalanceChart($evolution),
            'goals' => $vaultStatsService->getMonthlyGoalsProgress($bankroll, $entries),
            'recentEntries' => $vaultEntryRepository->findRecentForBankroll($bankroll, 15),
            'vaultStatsService' => $vaultStatsService,
            'goalsForm' => $this->createForm(VaultGoalsFormType::class, $bankroll)->createView(),
            'period' => $period,
        ]);
    }

    #[Route('/bankrolls/{bankrollId}/paris/nouveau', name: 'app_vault_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        int $bankrollId,
        Request $request,
        EntityManagerInterface $entityManager,
        VaultBetCalculator $vaultBetCalculator
    ): Response {
        $bankroll = $this->getBankrollOr404($bankrollId, $entityManager);
        $this->denyAccessUnlessGranted('BANKROLL_EDIT', $bankroll);

        $entry = new VaultEntry();
        $entry->setPlacedAt(new \DateTime());
        $form = $this->createForm(VaultEntryFormType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry->setBankroll($bankroll);
            $this->finalizeEntry($entry, $vaultBetCalculator);

            $entityManager->persist($entry);
            $entityManager->flush();

            $this->addFlash('success', 'Pari ajouté à ton NTS Vault.');

            return $this->redirectToRoute('app_vault_bankroll_detail', ['id' => $bankroll->getId()]);
        }

        return $this->render('vault/form.html.twig', [
            'form' => $form,
            'entry' => $entry,
            'bankroll' => $bankroll,
        ]);
    }

    #[Route('/bankrolls/{bankrollId}/paris/{id}/modifier', name: 'app_vault_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        int $bankrollId,
        VaultEntry $entry,
        Request $request,
        EntityManagerInterface $entityManager,
        VaultBetCalculator $vaultBetCalculator
    ): Response {
        $this->denyAccessUnlessGranted('VAULT_ENTRY_EDIT', $entry);

        if ($entry->getBankroll()->getId() !== $bankrollId) {
            throw $this->createNotFoundException();
        }

        $bankroll = $entry->getBankroll();

        $form = $this->createForm(VaultEntryFormType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->finalizeEntry($entry, $vaultBetCalculator);

            $entityManager->flush();

            $this->addFlash('success', 'Pari mis à jour.');

            return $this->redirectToRoute('app_vault_bankroll_detail', ['id' => $bankroll->getId()]);
        }

        return $this->render('vault/form.html.twig', [
            'form' => $form,
            'entry' => $entry,
            'bankroll' => $bankroll,
        ]);
    }

    /**
     * Prépare un pari pour l'enregistrement : associe les sélections, nettoie les
     * champs qui ne s'appliquent pas au type de pari, calcule le résultat final
     * (cote, statut, profit) et déduit un libellé d'affichage.
     */
    private function finalizeEntry(VaultEntry $entry, VaultBetCalculator $vaultBetCalculator): void
    {
        foreach ($entry->getSelections() as $selection) {
            $selection->setVaultEntry($entry);
        }

        if ($entry->isCombined() || $entry->isSysteme()) {
            $entry->setCategory(null);
            $entry->setHomeTeam(null);
            $entry->setAwayTeam(null);
            $entry->setWinner(null);
            $entry->setWinnerLabel(null);
        } else {
            // Pari simple/live : aucune sélection ni système ne s'applique.
            foreach ($entry->getSelections()->toArray() as $selection) {
                $entry->removeSelection($selection);
            }
        }

        if (!$entry->isSysteme()) {
            $entry->setSystemOption(null);
        }

        $vaultBetCalculator->calculate($entry);

        $entry->setLabel($this->buildLabel($entry));
    }

    private function buildLabel(VaultEntry $entry): string
    {
        if ($entry->isSysteme()) {
            $system = $entry->getSystemOption();

            return 'Système ' . ($system?->getLabel() ?? '') . ' (' . $entry->getSelections()->count() . ' sélections)';
        }

        if ($entry->isCombined()) {
            return 'Combiné (' . $entry->getSelections()->count() . ' sélections)';
        }

        if ($entry->getHomeTeam() && $entry->getAwayTeam()) {
            return $entry->getHomeTeam() . ' vs ' . $entry->getAwayTeam();
        }

        return $entry->getCategory() ?? 'Pari';
    }

    #[Route('/bankrolls/{bankrollId}/paris/{id}/supprimer', name: 'app_vault_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        int $bankrollId,
        VaultEntry $entry,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('VAULT_ENTRY_EDIT', $entry);

        if ($entry->getBankroll()->getId() !== $bankrollId) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete-vault-entry-' . $entry->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entry);
            $entityManager->flush();
            $this->addFlash('success', 'Pari supprimé.');
        }

        return $this->redirectToRoute('app_vault_bankroll_detail', ['id' => $bankrollId]);
    }

    #[Route('/bankrolls/{id}/objectifs', name: 'app_vault_goals', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function goals(Bankroll $bankroll, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('BANKROLL_EDIT', $bankroll);

        $form = $this->createForm(VaultGoalsFormType::class, $bankroll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Objectifs mis à jour.');
        }

        return $this->redirectToRoute('app_vault_bankroll_detail', ['id' => $bankroll->getId()]);
    }

    private function getBankrollOr404(int $bankrollId, EntityManagerInterface $entityManager): Bankroll
    {
        $bankroll = $entityManager->getRepository(Bankroll::class)->find($bankrollId);

        if (!$bankroll) {
            throw $this->createNotFoundException();
        }

        return $bankroll;
    }

    /**
     * Transforme l'évolution du solde en coordonnées SVG (courbe) pour le
     * graphique du Vault, avec un sous-ensemble de labels pour l'axe X.
     *
     * @param array<int, array{label: string, balance: float}> $evolution
     */
    private function buildBalanceChart(array $evolution): array
    {
        $count = count($evolution);

        if ($count === 0) {
            return ['polyline' => '', 'circles' => [], 'labels' => [], 'min' => 0, 'max' => 0];
        }

        $balances = array_map(static fn (array $p) => $p['balance'], $evolution);
        $min = min(0, min($balances));
        $max = max(0, max($balances));
        $range = ($max - $min) ?: 1;

        $lastIndex = max($count - 1, 1);
        $points = [];
        $circles = [];

        foreach (array_values($evolution) as $index => $point) {
            $x = round(($index / $lastIndex) * 500);
            $y = round(150 - (($point['balance'] - $min) / $range) * 150);
            $points[] = "{$x},{$y}";
            $circles[] = ['x' => $x, 'y' => $y];
        }

        // Affiche environ 6 labels sur l'axe X pour rester lisible
        $step = max(1, (int) ceil($count / 6));
        $labels = [];
        foreach (array_values($evolution) as $index => $point) {
            if ($index % $step === 0 || $index === $count - 1) {
                $labels[] = $point['label'];
            }
        }

        return [
            'polyline' => implode(' ', $points),
            'circles' => $circles,
            'labels' => $labels,
            'min' => round($min, 2),
            'max' => round($max, 2),
        ];
    }
}
