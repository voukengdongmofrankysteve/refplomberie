<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\EmailOptInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Préférences de notification du client.
 *
 * L'activation passe obligatoirement par un code envoyé à l'adresse : sans
 * cette preuve, la boutique pourrait écrire à n'importe qui.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly EmailOptInService $optIn) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/notifications', [
            'notifications' => [
                'email' => $user->notification_email,
                'verified' => $user->hasVerifiedNotificationEmail(),
                // Un code est en attente : l'écran ouvre directement sur la
                // saisie plutôt que de redemander l'adresse.
                'awaitingCode' => $user->notification_email !== null
                    && ! $user->hasVerifiedNotificationEmail()
                    && $user->emailVerificationCodes()
                        ->where('expires_at', '>', now())
                        ->exists(),
                'orderUpdates' => $user->notify_order_updates,
                'promotions' => $user->notify_promotions,
                'push' => $user->notify_push,
                'devices' => $user->deviceTokens()->count(),
                'accountEmail' => $user->email,
            ],
        ]);
    }

    /** Envoie — ou renvoie — le code de confirmation. */
    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ], attributes: ['email' => 'adresse email']);

        $this->optIn->sendCode($request->user(), $data['email']);

        return back()->with('success', 'Code envoyé. Vérifiez votre boîte de réception.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], attributes: ['code' => 'code'], messages: [
            'code.size' => 'Le code compte 6 chiffres.',
        ]);

        $this->optIn->confirm($request->user(), $data['code']);

        return back()->with('success', 'Adresse confirmée. Vos notifications sont activées.');
    }

    /**
     * Coche ou décoche un thème.
     *
     * Le consentement ne dépend pas d'une adresse confirmée : un client peut
     * vouloir les promotions par notification seule, sans donner d'email.
     * C'est la remise elle-même qui vérifie l'adresse — `acceptsEmail()` —
     * au moment de l'envoi.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notify_order_updates' => ['required', 'boolean'],
            'notify_promotions' => ['required', 'boolean'],
            // Facultatif : un formulaire qui ne pilote que les thèmes ne doit
            // pas être rejeté pour autant.
            'notify_push' => ['sometimes', 'boolean'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Préférences enregistrées.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->optIn->disable($request->user());

        return back()->with('success', 'Notifications par email désactivées.');
    }
}
