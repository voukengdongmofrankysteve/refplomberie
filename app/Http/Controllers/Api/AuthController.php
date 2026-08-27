<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleAccount;
use App\Services\GoogleIdToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Authentification de l'application mobile, par jeton Sanctum.
 *
 * L'application ne sert qu'aux clients : un compte administrateur ne peut pas
 * s'y connecter. Le back-office reste sur le web, derrière une session, ce qui
 * évite qu'un jeton volé sur un téléphone ouvre l'administration.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], attributes: [
            'name' => 'nom',
            'email' => 'email',
            'phone' => 'téléphone',
            'password' => 'mot de passe',
        ]);

        $user = User::create([
            ...$data,
            // Le rôle n'est jamais accepté depuis la requête : on ne crée que
            // des clients par cette porte.
            'role' => UserRole::Customer,
        ]);

        return response()->json([
            'token' => $this->issueToken($user, $request),
            'user' => $this->profile($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ], attributes: [
            'email' => 'email',
            'password' => 'mot de passe',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Un compte créé par Google n'a pas de mot de passe : la comparaison
        // lèverait une erreur de type, et ce client doit de toute façon
        // repasser par le bouton Google.
        if ($user === null
            || $user->password === null
            || ! Hash::check($data['password'], $user->password)) {
            // Message unique : distinguer « compte inconnu » de « mot de passe
            // faux » renseignerait un attaquant sur les comptes existants.
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        if ($user->isStaff()) {
            throw ValidationException::withMessages([
                'email' => 'L’administration se gère depuis le site web.',
            ]);
        }

        return response()->json([
            'token' => $this->issueToken($user, $request),
            'user' => $this->profile($user),
        ]);
    }

    /**
     * Connexion — ou inscription — par compte Google depuis l'application.
     *
     * L'application dialogue elle-même avec Google et nous transmet le jeton
     * d'identité signé qu'elle en reçoit. Il est vérifié auprès de Google
     * avant toute chose : sans cela, n'importe qui pourrait poster le jeton
     * de son choix et se faire passer pour un client.
     */
    public function google(Request $request, GoogleIdToken $verifier, GoogleAccount $accounts): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if (! $verifier->configured()) {
            throw ValidationException::withMessages([
                'id_token' => 'La connexion Google n’est pas configurée sur le serveur.',
            ]);
        }

        try {
            $payload = $verifier->verify($data['id_token']);

            $user = $accounts->resolve(
                googleId: $payload['sub'],
                email: $payload['email'],
                name: $payload['name'],
                avatar: $payload['picture'],
                emailVerified: $payload['email_verified'],
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['id_token' => $e->getMessage()]);
        }

        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'id_token' => 'L’administration se gère depuis le site web.',
            ]);
        }

        return response()->json([
            'token' => $this->issueToken($user, $request),
            'user' => $this->profile($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->profile($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ], attributes: ['name' => 'nom', 'phone' => 'téléphone', 'address' => 'adresse']);

        $user->update($data);

        return response()->json(['user' => $this->profile($user->fresh())]);
    }

    /** Déconnecte l'appareil courant, sans toucher aux autres. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    /**
     * Émet un jeton nommé d'après l'appareil, pour que le client puisse s'y
     * retrouver et révoquer un téléphone perdu.
     */
    private function issueToken(User $user, Request $request): string
    {
        $device = trim((string) $request->input('device_name', '')) ?: 'Application mobile';

        return $user->createToken($device)->plainTextToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'notifications' => [
                'email' => $user->notification_email,
                'verified' => $user->hasVerifiedNotificationEmail(),
                'orderUpdates' => $user->notify_order_updates,
                'promotions' => $user->notify_promotions,
            ],
        ];
    }
}
