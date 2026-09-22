<?php

namespace App\Controller;

use App\Entity\CommunityPost;
use App\Entity\User;
use App\Repository\PronosticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function pronostics(
        Request $request,
        PronosticRepository $pronosticRepository
    ): Response {
        $sport = $request->query->get('sport') ?: null;
        $access = $request->query->get('access') ?: null; // 'free' | 'vip'
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $result = $pronosticRepository->findFilteredPaginated($sport, $access, $page, $limit);

        /** @var User|null $user */
        $user = $this->getUser();
        $isVip = $user?->isVip() ?? false;

        return $this->render('public/pronostics.html.twig', [
            'pronostics' => $result['items'],
            'totalPronostics' => $result['total'],
            'page' => $page,
            'pageCount' => (int) ceil($result['total'] / $limit),
            'currentSport' => $sport,
            'currentAccess' => $access,
            'featured' => $pronosticRepository->findFeatured(),
            'nextVip' => $pronosticRepository->findNextVip(),
            'isVip' => $isVip,
        ]);
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