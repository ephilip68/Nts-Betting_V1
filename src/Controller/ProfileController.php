<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NotificationsFormType;
use App\Form\ProfilePasswordFormType;
use App\Form\PreferencesFormType;
use App\Form\ProfileInfoFormType;
use App\Repository\BankrollRepository;
use App\Repository\VaultEntryRepository;
use App\Service\AccountDeletionService;
use App\Service\StripeSubscriptionService;
use App\Service\VaultStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $user,
        StripeSubscriptionService $stripeSubscriptionService,
        BankrollRepository $bankrollRepository,
        VaultEntryRepository $vaultEntryRepository,
        VaultStatsService $vaultStatsService
    ): Response {
        $daysRemaining = null;
        $periodPercent = null;
        $periodEnd = $user->getSubscriptionCurrentPeriodEnd();

        if ($periodEnd) {
            $daysRemaining = max(0, (new \DateTime())->diff($periodEnd)->days);
            $periodPercent = max(0, min(100, (int) round(($daysRemaining / 30) * 100)));
        }

        $invoices = $user->getStripeCustomerId()
            ? $stripeSubscriptionService->listInvoices($user)
            : [];

        $vaultSummary = null;
        if ($user->hasVaultAccess()) {
            $vaultSummary = $vaultStatsService->getSummary($vaultEntryRepository->findAllForBankrolls($bankrollRepository->findAllForUser($user)));
        }

        return $this->render('profile/index.html.twig', [
            'infoForm' => $this->createForm(ProfileInfoFormType::class, $user)->createView(),
            'passwordForm' => $this->createForm(ProfilePasswordFormType::class)->createView(),
            'preferencesForm' => $this->createForm(PreferencesFormType::class, $user)->createView(),
            'notificationsForm' => $this->createForm(NotificationsFormType::class, $user)->createView(),
            'invoices' => $invoices,
            'vaultSummary' => $vaultSummary,
            'subscriptionDaysRemaining' => $daysRemaining,
            'subscriptionPeriodPercent' => $periodPercent,
        ]);
    }

    #[Route('/informations', name: 'app_profile_info', methods: ['POST'])]
    public function updateInfo(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ProfileInfoFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $slugger = new AsciiSlugger();
                $safeFilename = $slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/profile',
                        $newFilename
                    );
                    $user->setPhoto('/uploads/profile/' . $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', "Impossible d'enregistrer la photo. Réessaie.");

                    return $this->redirectToRoute('app_profile');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Informations mises à jour.');
        } else {
            $this->addFlash('error', 'Vérifie les informations saisies.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/securite', name: 'app_profile_security', methods: ['POST'])]
    public function updatePassword(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(ProfilePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');

                return $this->redirectToRoute('app_profile');
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe mis à jour.');
        } else {
            $this->addFlash('error', 'Vérifie les champs du mot de passe.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/preferences', name: 'app_profile_preferences', methods: ['POST'])]
    public function updatePreferences(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(PreferencesFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Préférences mises à jour.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/notifications', name: 'app_profile_notifications', methods: ['POST'])]
    public function updateNotifications(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(NotificationsFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Préférences de notifications mises à jour.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/supprimer', name: 'app_profile_delete', methods: ['POST'])]
    public function deleteAccount(
        #[CurrentUser] User $user,
        Request $request,
        AccountDeletionService $accountDeletionService,
        Security $security
    ): Response {
        if (!$this->isCsrfTokenValid('delete-account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Action refusée, réessaie.');

            return $this->redirectToRoute('app_profile');
        }

        $accountDeletionService->delete($user);

        $security->logout(false);

        $this->addFlash('success', 'Ton compte et toutes tes données ont été définitivement supprimés.');

        return $this->redirectToRoute('app_home');
    }
}
