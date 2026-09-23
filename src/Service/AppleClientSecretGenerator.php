<?php

namespace App\Service;

use Firebase\JWT\JWT;

/**
 * Génère le client_secret pour "Se connecter avec Apple". Contrairement à
 * Google, Apple n'utilise pas de secret fixe : c'est un JWT signé (ES256)
 * avec la clé privée .p8 téléchargée depuis le Apple Developer Portal,
 * valable 6 mois maximum. Voir la commande `app:apple:generate-client-secret`.
 */
class AppleClientSecretGenerator
{
    public function __construct(
        private readonly ?string $teamId,
        private readonly ?string $keyId,
        private readonly ?string $clientId,
        private readonly ?string $privateKeyPath,
    ) {
    }

    public function generate(int $validityInSeconds = 15777000): string
    {
        if (!$this->teamId || !$this->keyId || !$this->clientId || !$this->privateKeyPath) {
            throw new \RuntimeException(
                'APPLE_TEAM_ID, APPLE_KEY_ID, APPLE_CLIENT_ID et APPLE_PRIVATE_KEY_PATH doivent être renseignés dans .env.local.'
            );
        }

        if (!is_file($this->privateKeyPath)) {
            throw new \RuntimeException(sprintf('Clé privée Apple introuvable : "%s".', $this->privateKeyPath));
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        $now = time();

        $payload = [
            'iss' => $this->teamId,
            'iat' => $now,
            'exp' => $now + min($validityInSeconds, 15777000), // 6 mois max imposé par Apple
            'aud' => 'https://appleid.apple.com',
            'sub' => $this->clientId,
        ];

        return JWT::encode($payload, $privateKey, 'ES256', $this->keyId);
    }
}
