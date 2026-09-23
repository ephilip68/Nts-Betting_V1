<?php

namespace App\Controller\Admin;

use App\Form\SiteSettingFormType;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/parametres')]
#[IsGranted('ROLE_ADMIN')]
final class ParametresController extends AbstractController
{
    #[Route('', name: 'admin_parametres', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SiteSettingRepository $siteSettingRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $settings = $siteSettingRepository->getSettings();
        $form = $this->createForm(SiteSettingFormType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Paramètres mis à jour.');

            return $this->redirectToRoute('admin_parametres');
        }

        return $this->render('admin/parametres/index.html.twig', [
            'form' => $form,
        ]);
    }
}
