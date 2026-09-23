<?php

namespace App\Controller\Admin;

use App\Entity\Pronostic;
use App\Repository\PronosticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Notation rapide des pronostics : marquer gagné/perdu/annulé sans passer
 * par le formulaire complet d'édition. Complète PronosticController.
 */
#[Route('/admin/resultats')]
#[IsGranted('ROLE_ADMIN')]
final class ResultsController extends AbstractController
{
    #[Route('', name: 'admin_results_index', methods: ['GET'])]
    public function index(PronosticRepository $pronosticRepository): Response
    {
        return $this->render('admin/results/index.html.twig', [
            'pending' => $pronosticRepository->findBy(['status' => Pronostic::STATUS_PENDING], ['matchDate' => 'ASC']),
            'recentlyGraded' => $pronosticRepository->findBy(
                [],
                ['matchDate' => 'DESC'],
                10,
                0
            ),
        ]);
    }

    #[Route('/{id}/noter', name: 'admin_results_grade', methods: ['POST'])]
    public function grade(Pronostic $pronostic, Request $request, EntityManagerInterface $entityManager): Response
    {
        $status = $request->request->get('status');

        if (!$this->isCsrfTokenValid('grade-pronostic-' . $pronostic->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Action refusée, réessaie.');

            return $this->redirectToRoute('admin_results_index');
        }

        if (!in_array($status, [Pronostic::STATUS_WON, Pronostic::STATUS_LOST, Pronostic::STATUS_VOID, Pronostic::STATUS_PENDING], true)) {
            throw $this->createNotFoundException();
        }

        $pronostic->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Résultat mis à jour.');

        return $this->redirectToRoute('admin_results_index');
    }
}
