<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Connexion et inscription par compte Google.
 *
 * Un seul parcours pour les deux : Google ne distingue pas « s'inscrire » de
 * « se connecter », et le client non plus. S'il a déjà un compte on le
 * retrouve, sinon on le crée — dans les deux cas il arrive connecté.
 */
class GoogleController extends Controller
{
    public function __construct(private readonly GoogleAccount $accounts) {}

    /**
     * Envoie le client chez Google.
     */
    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()
                ->route('login')
                ->with('error', 'La connexion Google n’est pas encore configurée.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Retour de Google, une fois le client identifié chez eux.
     */
    public function callback(): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Refus du client, jeton expiré, retour bricolé à la main : rien
            // de tout cela ne mérite une page d'erreur.
            Log::warning('Connexion Google interrompue : '.$e->getMessage());

            return redirect()
                ->route('login')
                ->with('error', 'La connexion Google a échoué. Réessayez.');
        }

        try {
            $user = $this->accounts->resolve(
                googleId: (string) $google->getId(),
                email: $google->getEmail(),
                name: $google->getName() ?: $google->getNickname(),
                avatar: $google->getAvatar(),
                // Google renvoie ce drapeau dans le jeton d'identité ; sans
                // lui, on refuse de rattacher un compte existant.
                emailVerified: (bool) ($google->user['email_verified'] ?? false),
            );
        } catch (Throwable $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Les identifiants sont-ils renseignés ?
     *
     * Sans ce garde-fou, un déploiement où la variable manque afficherait au
     * client une exception de Socialite au lieu d'un message.
     */
    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
