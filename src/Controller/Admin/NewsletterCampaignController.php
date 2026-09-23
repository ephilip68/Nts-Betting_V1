<?php

namespace App\Controller\Admin;

use App\Entity\NewsletterCampaign;
use App\Form\NewsletterCampaignFormType;
use App\Repository\NewsletterCampaignRepository;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\CommonMark\CommonMarkConverter;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Campagnes de newsletter envoyées aux inscrits actifs ("Restez informé").
 * Envoi synchrone : adapté à la taille actuelle de la liste. Pour une liste
 * de plusieurs milliers d'inscrits, il faudrait passer par une file
 * d'attente asynchrone (Symfony Messenger) plutôt que d'envoyer dans la
 * requête HTTP.
 */
#[Route('/admin/newsletter/campagnes')]
#[IsGranted('ROLE_ADMIN')]
final class NewsletterCampaignController extends AbstractController
{
    #[Route('', name: 'admin_newsletter_campaign_index', methods: ['GET'])]
    public function index(NewsletterCampaignRepository $repository): Response
    {
        return $this->render('admin/newsletter/campaign_index.html.twig', [
            'campaigns' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/nouvelle', name: 'admin_newsletter_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campaign = new NewsletterCampaign();
        $form = $this->createForm(NewsletterCampaignFormType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($campaign);
            $entityManager->flush();

            $this->addFlash('success', 'Campagne enregistrée en brouillon.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        return $this->render('admin/newsletter/campaign_form.html.twig', [
            'form' => $form,
            'campaign' => $campaign,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_newsletter_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($campaign->isSent()) {
            $this->addFlash('error', 'Cette campagne a déjà été envoyée et ne peut plus être modifiée.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        $form = $this->createForm(NewsletterCampaignFormType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Campagne mise à jour.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        return $this->render('admin/newsletter/campaign_form.html.twig', [
            'form' => $form,
            'campaign' => $campaign,
        ]);
    }

    #[Route('/{id}/apercu', name: 'admin_newsletter_campaign_preview', methods: ['GET'])]
    public function preview(NewsletterCampaign $campaign): Response
    {
        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return $this->render('emails/newsletter_campaign.html.twig', [
            'subject' => $campaign->getSubject(),
            'contentHtml' => $converter->convert($campaign->getContent())->getContent(),
            'unsubscribeUrl' => '#',
        ]);
    }

    #[Route('/{id}/envoyer', name: 'admin_newsletter_campaign_send', methods: ['POST'])]
    public function send(
        NewsletterCampaign $campaign,
        Request $request,
        NewsletterSubscriberRepository $subscriberRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ): Response {
        if ($campaign->isSent()) {
            $this->addFlash('error', 'Cette campagne a déjà été envoyée.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        if (!$this->isCsrfTokenValid('send-campaign-' . $campaign->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Action refusée, réessaie.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        $subscribers = $subscriberRepository->findBy(['isActive' => true]);

        if ($subscribers === []) {
            $this->addFlash('error', 'Aucun inscrit actif à qui envoyer cette campagne.');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $contentHtml = $converter->convert($campaign->getContent())->getContent();

        $sentCount = 0;
        foreach ($subscribers as $subscriber) {
            $unsubscribeUrl = $urlGenerator->generate(
                'app_newsletter_unsubscribe',
                ['token' => $subscriber->getUnsubscribeToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@nts-betting.com', 'NTS Betting'))
                ->to($subscriber->getEmail())
                ->subject($campaign->getSubject())
                ->htmlTemplate('emails/newsletter_campaign.html.twig')
                ->context([
                    'subject' => $campaign->getSubject(),
                    'contentHtml' => $contentHtml,
                    'unsubscribeUrl' => $unsubscribeUrl,
                ]);

            try {
                $mailer->send($email);
                $sentCount++;
            } catch (\Throwable $e) {
                $logger->warning('Échec envoi newsletter à {email} : {error}', [
                    'email' => $subscriber->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $campaign->setStatus(NewsletterCampaign::STATUS_SENT);
        $campaign->setSentAt(new \DateTime());
        $campaign->setRecipientCount($sentCount);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Campagne envoyée à %d inscrit(s) sur %d.', $sentCount, count($subscribers)));

        return $this->redirectToRoute('admin_newsletter_campaign_index');
    }

    #[Route('/{id}/supprimer', name: 'admin_newsletter_campaign_delete', methods: ['POST'])]
    public function delete(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($campaign->isSent()) {
            $this->addFlash('error', 'Une campagne déjà envoyée ne peut pas être supprimée (historique).');

            return $this->redirectToRoute('admin_newsletter_campaign_index');
        }

        if ($this->isCsrfTokenValid('delete-campaign-' . $campaign->getId(), $request->request->get('_token'))) {
            $entityManager->remove($campaign);
            $entityManager->flush();
            $this->addFlash('success', 'Brouillon supprimé.');
        }

        return $this->redirectToRoute('admin_newsletter_campaign_index');
    }
}
