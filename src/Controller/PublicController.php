<?php

namespace App\Controller;

use App\Entity\CommunityPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('public/index.html.twig');
    }

    #[Route('/offres', name: 'app_offers')]
    public function offers(): Response
    {
        return $this->render('public/offers.html.twig');
    }

    #[Route('/resultats', name: 'app_results')]
    public function results(): Response
    {
        return $this->render('public/results.html.twig');
    }

    #[Route('/pronostics', name: 'app_pronostics')]
    public function pronostics(): Response
    {
        return $this->render('public/pronostics.html.twig');

    }

    #[Route('/community', name: 'app_community')]
    public function community(
        EntityManagerInterface $entityManager
    ): Response {
        $posts = $entityManager
            ->getRepository(CommunityPost::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('public/community.html.twig', [
            'posts' => $posts,
        ]);
    }
}