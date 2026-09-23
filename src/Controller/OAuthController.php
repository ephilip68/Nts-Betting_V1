<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Connexion via Google / Apple ("continuer avec..."). Les deux routes sont
 * prêtes ; elles ne deviendront réellement utilisables qu'une fois les
 * identifiants OAuth renseignés dans .env.local (voir README-oauth.md).
 */
final class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $clientRegistry->getClient('google')->fetchUser();
        } catch (IdentityProviderException $e) {
            $this->addFlash('error', 'La connexion avec Google a échoué. Réessaie.');

            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);

        if (!$user) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()]);
        }

        if (!$user) {
            $user = new User();
            $user->setEmail($googleUser->getEmail());
            $user->setNickname($googleUser->getName() ?: explode('@', $googleUser->getEmail())[0]);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(bin2hex(random_bytes(32)));
            $user->setDateInscription(new \DateTime());
            $user->setPoints(0);
            $user->setIsVerified(true); // L'e-mail est déjà vérifié par Google
            $entityManager->persist($user);
        }

        if (!$user->getGoogleId()) {
            $user->setGoogleId($googleUser->getId());
        }

        $entityManager->flush();

        $security->login($user, 'main');

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/connect/apple', name: 'connect_apple')]
    public function connectApple(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('apple')->redirect(['name', 'email']);
    }

    #[Route('/connect/apple/check', name: 'connect_apple_check')]
    public function connectAppleCheck(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        try {
            $appleClient = $clientRegistry->getClient('apple');
            $accessToken = $appleClient->getAccessToken();
            $appleUser = $appleClient->fetchUserFromToken($accessToken);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'La connexion avec Apple a échoué. Réessaie.');

            return $this->redirectToRoute('app_login');
        }

        $appleId = $appleUser->getId();
        $email = $appleUser->toArray()['email'] ?? null;

        $user = $entityManager->getRepository(User::class)->findOneBy(['appleId' => $appleId]);

        if (!$user && $email) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        }

        if (!$user) {
            if (!$email) {
                $this->addFlash('error', "Impossible de récupérer ton e-mail depuis Apple. Utilise l'inscription classique.");

                return $this->redirectToRoute('app_login');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setNickname(explode('@', $email)[0]);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(bin2hex(random_bytes(32)));
            $user->setDateInscription(new \DateTime());
            $user->setPoints(0);
            $user->setIsVerified(true);
            $entityManager->persist($user);
        }

        if (!$user->getAppleId()) {
            $user->setAppleId($appleId);
        }

        $entityManager->flush();

        $security->login($user, 'main');

        return $this->redirectToRoute('app_dashboard');
    }
}
