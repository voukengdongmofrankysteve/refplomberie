<?php

namespace App\Services;

use App\Models\DeviceToken;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de notifications push par Firebase Cloud Messaging.
 *
 * L'API HTTP v1 est la seule encore ouverte : l'ancienne interface à « clé
 * serveur » a été fermée par Google en juin 2024. Elle s'authentifie par un
 * jeton OAuth2 dérivé d'un compte de service, et non par une clé statique.
 *
 * @see https://firebase.google.com/docs/cloud-messaging/migrate-v1
 */
class FirebaseMessaging
{
    /** Le jeton d'accès vaut une heure ; on le renouvelle un peu avant. */
    private const TOKEN_TTL = 3300;

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** L'appareil a désinstallé l'application ou son jeton a expiré. */
    private const GONE = ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'];

    /** La configuration permet-elle d'envoyer ? */
    public function isConfigured(): bool
    {
        return $this->projectId() !== null && $this->credentialsPath() !== null;
    }

    /**
     * Envoie une notification à tous les appareils d'un utilisateur.
     *
     * @param  array<string, string>  $data  Charge utile lue par l'application
     *                                       pour ouvrir le bon écran.
     * @return int Nombre d'appareils réellement atteints.
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null,
    ): int {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();

        return $this->sendToTokens($tokens, $title, $body, $data, $imageUrl);
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, string>  $data
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null,
    ): int {
        if ($tokens === [] || ! $this->isConfigured()) {
            return 0;
        }

        $sent = 0;

        foreach ($tokens as $token) {
            if ($this->sendOne($token, $title, $body, $data, $imageUrl)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function sendOne(
        string $token,
        string $title,
        string $body,
        array $data,
        ?string $imageUrl,
    ): bool {
        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post($this->endpoint(), [
                    'message' => $this->payload($token, $title, $body, $data, $imageUrl),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Push injoignable', ['message' => $e->getMessage()]);

            return false;
        } catch (RuntimeException $e) {
            Log::warning('Push non configuré', ['message' => $e->getMessage()]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        $this->handleFailure($token, $response->json('error.details.0.errorCode'), $response->body());

        return false;
    }

    /**
     * Charge utile FCM.
     *
     * Le bloc `notification` fait apparaître la bannière quand l'application
     * est fermée ; `data` est ce que l'application relit pour savoir où
     * emmener le client au clic.
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function payload(
        string $token,
        string $title,
        string $body,
        array $data,
        ?string $imageUrl,
    ): array {
        $notification = array_filter([
            'title' => $title,
            'body' => $body,
            'image' => $imageUrl,
        ]);

        return [
            'token' => $token,
            'notification' => $notification,
            // FCM n'accepte que des chaînes dans `data`.
            'data' => array_map(fn ($value): string => (string) $value, $data),
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'refplomberie_default',
                    'color' => '#25D366',
                ],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
            ],
            'webpush' => [
                'notification' => [
                    ...$notification,
                    'icon' => url('/favicon-192.png'),
                ],
                'fcm_options' => ['link' => $data['url'] ?? url('/')],
            ],
        ];
    }

    /**
     * Réagit à un refus de FCM.
     *
     * Un jeton révoqué est supprimé : le garder ferait échouer chaque envoi
     * suivant et fausserait les compteurs.
     */
    private function handleFailure(string $token, ?string $errorCode, string $body): void
    {
        if ($errorCode !== null && in_array($errorCode, self::GONE, strict: true)) {
            DeviceToken::where('token', $token)->delete();

            return;
        }

        Log::warning('Push refusé par FCM', ['erreur' => $errorCode, 'corps' => $body]);
    }

    /**
     * Jeton OAuth2 du compte de service, mis en cache le temps de sa validité.
     */
    private function accessToken(): string
    {
        return Cache::remember('fcm-access-token', self::TOKEN_TTL, function (): string {
            $path = $this->credentialsPath();

            if ($path === null) {
                throw new RuntimeException(
                    'Renseignez FIREBASE_CREDENTIALS avec le chemin du compte de service.',
                );
            }

            $credentials = new ServiceAccountCredentials(self::SCOPE, $path);
            $token = $credentials->fetchAuthToken()['access_token'] ?? null;

            if (! is_string($token)) {
                throw new RuntimeException('Firebase a refusé le compte de service.');
            }

            return $token;
        });
    }

    private function endpoint(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/'.$this->projectId().'/messages:send';
    }

    private function projectId(): ?string
    {
        $projectId = config('services.firebase.project_id');

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    /** Chemin du compte de service, s'il existe réellement sur le disque. */
    private function credentialsPath(): ?string
    {
        $path = config('services.firebase.credentials');

        if (! is_string($path) || $path === '') {
            return null;
        }

        $resolved = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
            ? $path
            : base_path($path);

        return is_readable($resolved) ? $resolved : null;
    }
}
