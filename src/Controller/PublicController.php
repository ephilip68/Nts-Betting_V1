<?php

namespace App\Controller;

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
}