<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/blog')]
#[IsGranted('ROLE_ADMIN')]
final class ArticleController extends AbstractController
{
    #[Route('', name: 'admin_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $articleRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleCoverUpload($form, $article);
            $this->syncPublishedAt($article);

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article créé.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'article' => $article,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_article_edit', methods: ['GET', 'POST'])]
    public function edit(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleCoverUpload($form, $article);
            $this->syncPublishedAt($article);

            $entityManager->flush();

            $this->addFlash('success', 'Article mis à jour.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'article' => $article,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_article_delete', methods: ['POST'])]
    public function delete(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-article-' . $article->getId(), $request->request->get('_token'))) {
            if ($article->getCoverImage()) {
                $path = $this->getParameter('kernel.project_dir') . '/public' . $article->getCoverImage();
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article supprimé.');
        }

        return $this->redirectToRoute('admin_article_index');
    }

    private function handleCoverUpload(FormInterface $form, Article $article): void
    {
        /** @var UploadedFile|null $coverFile */
        $coverFile = $form->get('coverImageFile')->getData();

        if (!$coverFile) {
            return;
        }

        $slugger = new AsciiSlugger();
        $safeFilename = $slugger->slug(pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $coverFile->guessExtension();

        try {
            $coverFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/blog',
                $newFilename
            );
            $article->setCoverImage('/uploads/blog/' . $newFilename);
        } catch (FileException) {
            $this->addFlash('error', "Impossible d'enregistrer l'image de couverture.");
        }
    }

    private function syncPublishedAt(Article $article): void
    {
        if ($article->getStatus() === 'published' && !$article->getPublishedAt()) {
            $article->setPublishedAt(new \DateTime());
        }
    }
}
