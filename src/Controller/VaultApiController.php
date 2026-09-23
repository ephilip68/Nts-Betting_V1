<?php

namespace App\Controller;

use App\Repository\SystemOptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Données dynamiques utilisées par le formulaire d'ajout de pari du NTS Vault
 * (options de résultat par sport, systèmes disponibles selon le nombre de
 * sélections). Réservé aux membres connectés, comme le reste du Vault.
 */
#[Route('/api/vault')]
#[IsGranted('ROLE_USER')]
class VaultApiController extends AbstractController
{
    #[Route('/winner-options', name: 'api_vault_winner_options', methods: ['GET'])]
    public function winnerOptions(Request $request): JsonResponse
    {
        $sport = $request->query->get('sport');

        $winnerOptionsBySport = include __DIR__ . '/../../config/vault_data/winner_options.php';

        if (!$sport || !isset($winnerOptionsBySport[$sport])) {
            $sport = 'Autre';
        }

        return new JsonResponse($winnerOptionsBySport[$sport]);
    }

    #[Route('/system-options', name: 'api_vault_system_options', methods: ['GET'])]
    public function systemOptions(Request $request, SystemOptionRepository $systemOptionRepository): JsonResponse
    {
        $matches = (int) $request->query->get('matches', 0);

        $standard = [];
        $special = [];

        foreach ($systemOptionRepository->findForMatchCount($matches) as $option) {
            $data = [
                'id' => $option->getId(),
                'name' => $option->getLabel(),
                'description' => $option->getValue(),
            ];

            if (preg_match('/^\d+\/\d+$/', $option->getLabel())) {
                $standard[] = $data;
            } else {
                $special[] = $data;
            }
        }

        return $this->json(['standard' => $standard, 'special' => $special]);
    }
}
