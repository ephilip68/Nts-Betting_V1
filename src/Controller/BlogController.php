<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\ArticleRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog')]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $category = $request->query->get('category') ?: null;
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 9;

        $result = $articleRepository->findPublishedFiltered(
            category: $category,
            search: $search ?: null,
            page: $page,
            limit: $limit
        );

        return $this->render('public/blog/index.html.twig', [
            'articles' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / $limit)),
            'featured' => $page === 1 && !$category && !$search ? $articleRepository->findFeatured(3) : [],
            'category' => $category,
            'search' => $search,
            'categories' => Article::CATEGORIES,
        ]);
    }

    #[Route('/blog/{slug}', name: 'app_blog_show')]
    public function show(string $slug, ArticleRepository $articleRepository, ArticleRenderer $articleRenderer): Response
    {
        $article = $articleRepository->findOnePublishedBySlug($slug);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $rendered = $articleRenderer->render($article->getContent());

        return $this->render('public/blog/show.html.twig', [
            'article' => $article,
            'contentHtml' => $rendered['html'],
            'toc' => $rendered['toc'],
            'similarArticles' => $articleRepository->findSimilar($article),
        ]);
    }
}
