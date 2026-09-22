<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mailer\MailerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, VerifyEmailHelperInterface $verifyEmailHelper, MailerInterface $mailer): Response
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setPoints(0);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword($user, $plainPassword)
        );

        $user->setDateInscription(new \DateTime());

        $entityManager->persist($user);
        $entityManager->flush();

        $signatureComponents = $verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@nts-betting.com', 'NTS Betting'))
            ->to((string) $user->getEmail())
            ->subject('Vérifiez votre adresse e-mail — NTS Betting')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
            ]);
        
        $request->getSession()->set(
            'verification_email',
            $user->getEmail()
        );

        $mailer->send($email);

        return $this->redirectToRoute('app_verify_email_pending');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager
            ->getRepository(User::class)
            ->find($request->query->get('id'));

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail()
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'verify_email_error',
                'Le lien de vérification est invalide ou a expiré.'
            );

            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_verify_email_pending');
    }

    #[Route('/verification-email', name: 'app_verify_email_pending')]
    public function verifyEmailPending(): Response
    {
        return $this->render('registration/verify_email.html.twig');
    }

    #[Route('/verification-email/status', name: 'app_verify_email_status', methods: ['GET'])]
    public function verifyEmailStatus(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $email = $request->getSession()->get('verification_email');

        if (!$email) {
            return $this->json([
                'verified' => false,
            ]);
        }

        $user = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        return $this->json([
            'verified' => $user?->isVerified() ?? false,
        ]);
    }
}
