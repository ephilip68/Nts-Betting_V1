<?php

namespace App\Controller\Admin;

use App\Entity\Pronostic;
use App\Form\PronosticFormType;
use App\Repository\PronosticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pronostics')]
#[IsGranted('ROLE_ADMIN')]
final class PronosticController extends AbstractController
{
    #[Route('', name: 'admin_pronostic_index', methods: ['GET'])]
    public function index(PronosticRepository $pronosticRepository): Response
    {
        return $this->render('admin/pronostic/index.html.twig', [
            'pronostics' => $pronosticRepository->findBy([], ['matchDate' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_pronostic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pronostic = new Pronostic();
        $form = $this->createForm(PronosticFormType::class, $pronostic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pronostic);
            $entityManager->flush();

            $this->addFlash('success', 'Pronostic créé.');

            return $this->redirectToRoute('admin_pronostic_index');
        }

        return $this->render('admin/pronostic/form.html.twig', [
            'form' => $form,
            'pronostic' => $pronostic,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_pronostic_edit', methods: ['GET', 'POST'])]
    public function edit(Pronostic $pronostic, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PronosticFormType::class, $pronostic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Pronostic mis à jour.');

            return $this->redirectToRoute('admin_pronostic_index');
        }

        return $this->render('admin/pronostic/form.html.twig', [
            'form' => $form,
            'pronostic' => $pronostic,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_pronostic_delete', methods: ['POST'])]
    public function delete(Pronostic $pronostic, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-pronostic-' . $pronostic->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pronostic);
            $entityManager->flush();

            $this->addFlash('success', 'Pronostic supprimé.');
        }

        return $this->redirectToRoute('admin_pronostic_index');
    }
}
