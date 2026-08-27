<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Vérifie un jeton d'identité Google présenté par l'application mobile.
 *
 * L'application se connecte à Google elle-même, puis nous transmet le jeton
 * signé qu'elle en a reçu. Impossible de le croire sur parole : n'importe qui
 * pourrait poster un jeton obtenu ailleurs, ou fabriqué de toutes pièces.
 *
 * La vérification passe par le point d'entrée public de Google, qui contrôle
 * la signature et l'expiration. Reste à notre charge le contrôle décisif :
 * `aud`, c'est-à-dire l'application à laquelle le jeton a été délivré. Sans
 * lui, un jeton obtenu par n'importe quelle autre application Google ouvrirait
 * un compte chez nous.
 */
class GoogleIdToken
{
    private const ENDPOINT = 'https://oauth2.googleapis.com/tokeninfo';

    /** Émetteurs légitimes d'un jeton d'identité Google. */
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @return array{sub: string, email: string|null, name: string|null, picture: string|null, email_verified: bool}
     */
    public function verify(string $token): array
    {
        try {
            $response = Http::timeout(10)->get(self::ENDPOINT, ['id_token' => $token]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Impossible de joindre Google pour vérifier votre connexion. '
                .'Réessayez dans un instant.',
            );
        }

        if ($response->failed()) {
            throw new RuntimeException('Connexion Google refusée : jeton invalide ou expiré.');
        }

        $payload = $response->json();

        if (! is_array($payload) || blank($payload['sub'] ?? null)) {
            throw new RuntimeException('Réponse inattendue de Google.');
        }

        if (! in_array($payload['iss'] ?? '', self::ISSUERS, strict: true)) {
            throw new RuntimeException('Connexion Google refusée : émetteur inconnu.');
        }

        if (! in_array($payload['aud'] ?? '', $this->audiences(), strict: true)) {
            throw new RuntimeException(
                'Connexion Google refusée : ce jeton a été délivré à une autre application.',
            );
        }

        return [
            'sub' => (string) $payload['sub'],
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
            'picture' => $payload['picture'] ?? null,
            // Google renvoie parfois la chaîne « true » plutôt qu'un booléen.
            'email_verified' => filter_var(
                $payload['email_verified'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
        ];
    }

    public function configured(): bool
    {
        return $this->audiences() !== [];
    }

    /**
     * Identifiants clients acceptés : ceux de l'application mobile, plus
     * celui du site — Google délivre parfois un jeton portant l'identifiant
     * « serveur » quand l'application le réclame explicitement.
     *
     * @return array<int, string>
     */
    private function audiences(): array
    {
        return array_values(array_filter([
            ...(array) config('services.google.mobile_client_ids', []),
            config('services.google.client_id'),
        ]));
    }
}
