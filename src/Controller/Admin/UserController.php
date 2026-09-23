<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserEditFormType;
use App\Repository\UserRepository;
use App\Service\AccountDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $plan = $request->query->get('plan') ?: null;
        $page = max(1, (int) $request->query->get('page', 1));

        $result = $userRepository->findFiltered($search ?: null, $plan, $page, 25);

        return $this->render('admin/user/index.html.twig', [
            'users' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / 25)),
            'search' => $search,
            'plan' => $plan,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminUserEditFormType::class, $user);
        $form->get('isAdmin')->setData(in_array('ROLE_ADMIN', $user->getRoles(), true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roles = $form->get('isAdmin')->getData() ? ['ROLE_ADMIN'] : [];
            $user->setRoles($roles);

            $entityManager->flush();

            $this->addFlash('success', 'Membre mis à jour.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', [
            'form' => $form,
            'editedUser' => $user,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        User $user,
        #[CurrentUser] User $admin,
        Request $request,
        AccountDeletionService $accountDeletionService
    ): Response {
        if ($user === $admin) {
            $this->addFlash('error', 'Tu ne peux pas supprimer ton propre compte depuis cette page.');

            return $this->redirectToRoute('admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $accountDeletionService->delete($user);
            $this->addFlash('success', 'Membre supprimé.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
