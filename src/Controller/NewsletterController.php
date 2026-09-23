<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NewsletterController extends AbstractController
{
    #[Route('/newsletter/inscription', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterSubscriberRepository $repository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $redirect = $request->request->get('redirect');
        $redirect = $redirect && str_starts_with($redirect, $request->getSchemeAndHttpHost())
            ? $redirect
            : $this->generateUrl('app_home');

        if (!$this->isCsrfTokenValid('newsletter-subscribe', $request->request->get('_token'))) {
            $this->addFlash('newsletter_error', 'Action refusée, réessaie.');

            return $this->redirect($redirect);
        }

        $email = trim((string) $request->request->get('email', ''));
        $errors = $validator->validate($email, [new Email(message: 'Adresse e-mail invalide.')]);

        if (count($errors) > 0 || $email === '') {
            $this->addFlash('newsletter_error', 'Merci de renseigner une adresse e-mail valide.');

            return $this->redirect($redirect);
        }

        $existing = $repository->findOneBy(['email' => $email]);

        if ($existing) {
            if (!$existing->isActive()) {
                $existing->setIsActive(true);
                $existing->setUnsubscribedAt(null);
                $entityManager->flush();
            }

            $this->addFlash('newsletter_success', 'Tu es déjà inscrit(e) à la newsletter !');

            return $this->redirect($redirect);
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail($email);
        $entityManager->persist($subscriber);
        $entityManager->flush();

        $this->addFlash('newsletter_success', 'Inscription confirmée, merci ! Tu recevras nos prochaines analyses et actualités.');

        return $this->redirect($redirect);
    }

    #[Route('/newsletter/desinscription/{token}', name: 'app_newsletter_unsubscribe')]
    public function unsubscribe(
        string $token,
        NewsletterSubscriberRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $subscriber = $repository->findOneBy(['unsubscribeToken' => $token]);

        if ($subscriber && $subscriber->isActive()) {
            $subscriber->setIsActive(false);
            $subscriber->setUnsubscribedAt(new \DateTime());
            $entityManager->flush();
        }

        return $this->render('public/newsletter_unsubscribed.html.twig', [
            'found' => $subscriber !== null,
        ]);
    }
}
