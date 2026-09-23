<?php

namespace App\Controller;

use App\Entity\PronosticFavorite;
use App\Entity\User;
use App\Repository\CommunityPostRepository;
use App\Repository\PronosticFavoriteRepository;
use App\Repository\PronosticRepository;
use App\Repository\UserRepository;
use App\Service\ResultsStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PublicController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        PronosticRepository $pronosticRepository,
        UserRepository $userRepository,
        ResultsStatsService $resultsStatsService
    ): Response {
        $settledLast30Days = $pronosticRepository->findSettledFiltered(periodDays: 30, page: 1, limit: 1000)['items'];
        $stats = $resultsStatsService->getGlobalStats($settledLast30Days);

        return $this->render('public/index.html.twig', [
            'heroStats' => $stats,
            'activeMembersCount' => $userRepository->count(['subscriptionStatus' => 'active']),
            'latestResults' => $pronosticRepository->findSettledFiltered(page: 1, limit: 5)['items'],
        ]);
    }

    #[Route('/offres', name: 'app_offers')]
    public function offers(): Response
    {
        return $this->render('public/offers.html.twig');
    }

    #[Route('/resultats', name: 'app_results')]
    public function results(
        Request $request,
        PronosticRepository $pronosticRepository,
        UserRepository $userRepository,
        ResultsStatsService $statsService
    ): Response {
        $sport = $request->query->get('sport') ?: null;
        $competition = $request->query->get('competition') ?: null;
        $betType = $request->query->get('type') ?: null;
        $status = $request->query->get('status') ?: null;
        $periodDays = (int) ($request->query->get('period') ?: 30);

        $settledAll = $pronosticRepository->findAllSettled();
        $monthlyPerformance = $statsService->getMonthlyPerformance($settledAll);
        $chart = $this->buildPerformanceChart($monthlyPerformance);

        $latest = $pronosticRepository->findSettledFiltered(
            sport: $sport,
            competition: $competition,
            betType: $betType,
            status: $status,
            periodDays: $periodDays,
            page: 1,
            limit: 20
        );

        $recentTickets = $pronosticRepository->findSettledFiltered(page: 1, limit: 3);

        return $this->render('public/results.html.twig', [
            'stats' => $statsService->getGlobalStats($settledAll),
            'monthlyPerformance' => $monthlyPerformance,
            'chart' => $chart,
            'sportDistribution' => $statsService->getSportDistribution($settledAll),
            'latestResults' => $latest['items'],
            'latestResultsTotal' => $latest['total'],
            'recentTickets' => $recentTickets['items'],
            'statsService' => $statsService,
            'filters' => [
                'sport' => $sport,
                'competition' => $competition,
                'type' => $betType,
                'status' => $status,
                'period' => $periodDays,
            ],
            'availableSports' => $pronosticRepository->findDistinctSettledSports(),
            'availableCompetitions' => $pronosticRepository->findDistinctSettledCompetitions(),
            'availableBetTypes' => $pronosticRepository->findDistinctSettledBetTypes(),
            'activeMembersCount' => $userRepository->count(['subscriptionStatus' => 'active']),
        ]);
    }

    /**
     * Transforme la performance mensuelle en coordonnées prêtes à afficher
     * (barres + polyline SVG) pour le composant results/_analytics.
     *
     * @param array<int, array{label: string, unitsWon: float, winRate: float, count: int}> $monthlyPerformance
     */
    private function buildPerformanceChart(array $monthlyPerformance): array
    {
        $maxUnits = max(array_map(static fn (array $m) => $m['unitsWon'], $monthlyPerformance)) ?: 1;
        $lastIndex = max(count($monthlyPerformance) - 1, 1);
        $niceMax = max(5, (int) (ceil($maxUnits / 5) * 5));

        $bars = [];
        $points = [];
        $circles = [];

        foreach (array_values($monthlyPerformance) as $index => $month) {
            $bars[] = [
                'label' => $month['label'],
                'unitsWon' => $month['unitsWon'],
                'heightPercent' => $month['unitsWon'] > 0 ? max(4, round(($month['unitsWon'] / $niceMax) * 100)) : 0,
            ];

            $x = round(($index / $lastIndex) * 500);
            $y = round(150 - ($month['winRate'] / 100 * 150));
            $points[] = "{$x},{$y}";
            $circles[] = ['x' => $x, 'y' => $y];
        }

        return [
            'bars' => $bars,
            'polyline' => implode(' ', $points),
            'circles' => $circles,
            'yAxisLabels' => [
                $niceMax,
                (int) round($niceMax * 0.75),
                (int) round($niceMax * 0.5),
                (int) round($niceMax * 0.25),
                0,
            ],
        ];
    }

    #[Route('/pronostics', name: 'app_pronostics')]
    public function pronostics(
        Request $request,
        PronosticRepository $pronosticRepository,
        PronosticFavoriteRepository $favoriteRepository,
        ResultsStatsService $resultsStatsService
    ): Response {
        $sport = $request->query->get('sport') ?: null;
        $access = $request->query->get('access') ?: null; // 'free' | 'vip'
        $competition = $request->query->get('competition') ?: null;
        $betType = $request->query->get('type') ?: null;
        $minConfidence = (int) $request->query->get('confidence', 0) ?: null;
        $search = trim((string) $request->query->get('q', '')) ?: null;
        $sort = $request->query->get('sort', 'recent');
        $onlyFavorites = $request->query->get('favorites') === '1';
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        /** @var User|null $user */
        $user = $this->getUser();
        $isVip = $user?->isVip() ?? false;

        $favoriteIds = $user ? $favoriteRepository->findFavoritePronosticIds($user) : [];

        $result = $pronosticRepository->findFilteredPaginated(
            sport: $sport,
            access: $access,
            page: $page,
            limit: $limit,
            competition: $competition,
            betType: $betType,
            minConfidence: $minConfidence,
            search: $search,
            sort: $sort,
            onlyIds: $onlyFavorites ? $favoriteIds : null
        );

        $settledThisMonth = array_values(array_filter(
            $pronosticRepository->findAllSettled(),
            static fn ($p) => $p->getMatchDate() >= new \DateTime('first day of this month midnight')
        ));

        return $this->render('public/pronostics.html.twig', [
            'pronostics' => $result['items'],
            'totalPronostics' => $result['total'],
            'page' => $page,
            'pageCount' => (int) ceil($result['total'] / $limit),
            'currentSport' => $sport,
            'currentAccess' => $access,
            'currentCompetition' => $competition,
            'currentBetType' => $betType,
            'currentConfidence' => $minConfidence,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'onlyFavorites' => $onlyFavorites,
            'favoriteIds' => $favoriteIds,
            'availableCompetitions' => $pronosticRepository->findDistinctCompetitions(),
            'availableBetTypes' => $pronosticRepository->findDistinctBetTypes(),
            'monthStats' => $resultsStatsService->getGlobalStats($settledThisMonth),
            'featured' => $pronosticRepository->findFeatured(),
            'nextVip' => $pronosticRepository->findNextVip(),
            'isVip' => $isVip,
        ]);
    }

    #[Route('/pronostics/{id}/favori', name: 'app_pronostic_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleFavorite(
        int $id,
        Request $request,
        PronosticRepository $pronosticRepository,
        PronosticFavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('toggle-favorite-' . $id, $request->request->get('_token'))) {
            return $this->redirectToRoute('app_pronostics');
        }

        $pronostic = $pronosticRepository->find($id);

        if (!$pronostic) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $existing = $favoriteRepository->findOneBy(['user' => $user, 'pronostic' => $pronostic]);

        if ($existing) {
            $entityManager->remove($existing);
        } else {
            $favorite = new PronosticFavorite();
            $favorite->setUser($user);
            $favorite->setPronostic($pronostic);
            $entityManager->persist($favorite);
        }

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_pronostics'));
    }

    #[Route('/community', name: 'app_community')]
    public function community(
        Request $request,
        CommunityPostRepository $communityPostRepository,
        UserRepository $userRepository
    ): Response {
        $type = $request->query->get('type') ?: null;
        $sport = $request->query->get('sport') ?: null;
        $search = trim((string) $request->query->get('q', '')) ?: null;
        $sort = $request->query->get('sort', 'recent');
        $period = $request->query->get('period', 'week'); // week | month | all

        $since = match ($period) {
            'month' => (new \DateTime())->modify('-30 days'),
            'week' => (new \DateTime())->modify('-7 days'),
            default => null,
        };

        return $this->render('public/community.html.twig', [
            'posts' => $communityPostRepository->findFiltered($type, $sport, $search, $sort),
            'currentType' => $type,
            'currentSport' => $sport,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentPeriod' => $period,
            'topContributors' => $communityPostRepository->getTopContributors($since, 5),
            'postsBySport' => $communityPostRepository->countBySport(),
            'activeMembersCount' => $userRepository->count(['subscriptionStatus' => 'active']),
        ]);
    }
}