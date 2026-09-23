<?php

namespace App\Controller\Admin;

use App\Repository\NewsletterSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/newsletter')]
#[IsGranted('ROLE_ADMIN')]
final class NewsletterController extends AbstractController
{
    #[Route('', name: 'admin_newsletter_index', methods: ['GET'])]
    public function index(NewsletterSubscriberRepository $repository): Response
    {
        return $this->render('admin/newsletter/index.html.twig', [
            'subscribers' => $repository->findBy([], ['subscribedAt' => 'DESC']),
            'activeCount' => $repository->count(['isActive' => true]),
        ]);
    }

    #[Route('/export.csv', name: 'admin_newsletter_export', methods: ['GET'])]
    public function export(NewsletterSubscriberRepository $repository): Response
    {
        $subscribers = $repository->findBy(['isActive' => true], ['subscribedAt' => 'ASC']);

        $csv = "email,date_inscription\n";
        foreach ($subscribers as $subscriber) {
            $csv .= sprintf('"%s","%s"' . "\n", $subscriber->getEmail(), $subscriber->getSubscribedAt()->format('Y-m-d H:i'));
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
