<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Activation des notifications par email, confirmée par code à usage unique.
 *
 * Rien ne part vers une adresse que son propriétaire n'a pas confirmée : une
 * faute de frappe enverrait sinon les commandes d'un client chez un inconnu,
 * et une adresse saisie au hasard ferait de la boutique un outil de spam.
 */
class EmailOptInService
{
    /** Durée de vie du code, assez courte pour rester une preuve fraîche. */
    private const TTL_MINUTES = 15;

    /** Délai avant de pouvoir redemander un code. */
    private const RESEND_SECONDS = 60;

    /**
     * Envoie un code à l'adresse indiquée et suspend les envois en attendant.
     *
     * @throws ValidationException
     */
    public function sendCode(User $user, string $email): void
    {
        $email = mb_strtolower(trim($email));
        $pending = $this->pendingFor($user, $email);

        if ($pending !== null && $pending->created_at?->diffInSeconds(now()) < self::RESEND_SECONDS) {
            throw ValidationException::withMessages([
                'email' => 'Un code vient d’être envoyé. Patientez une minute avant d’en redemander un.',
            ]);
        }

        $code = $this->generateCode();

        // L'envoi passe avant l'enregistrement : si le serveur mail est
        // injoignable, mieux vaut ne rien changer que laisser le client
        // devant un écran réclamant un code qui n'arrivera jamais.
        try {
            Mail::to($email)->send(new EmailVerificationCodeMail(
                $user->name,
                $code,
                self::TTL_MINUTES,
            ));
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => $this->deliveryFailureMessage($e),
            ]);
        }

        // Changer d'adresse invalide la précédente confirmation : le
        // consentement porte sur une adresse, pas sur le compte.
        if ($user->notification_email !== $email || ! $user->hasVerifiedNotificationEmail()) {
            $user->resetNotificationEmail($email);
        }

        $user->emailVerificationCodes()->delete();

        $user->emailVerificationCodes()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);
    }

    /**
     * Confirme l'adresse et active les notifications demandées.
     *
     * @throws ValidationException
     */
    public function confirm(User $user, string $code): void
    {
        $record = $user->emailVerificationCodes()->latest()->first();

        if ($record === null || $record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => 'Ce code a expiré. Demandez-en un nouveau.',
            ]);
        }

        if ($record->isExhausted()) {
            $record->delete();

            throw ValidationException::withMessages([
                'code' => 'Trop de tentatives. Demandez un nouveau code.',
            ]);
        }

        if (! $record->matches($code)) {
            $record->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'Code incorrect.',
            ]);
        }

        $user->forceFill([
            'notification_email' => $record->email,
            'notification_email_verified_at' => now(),
            // Sans consentement explicite l'activation n'aurait aucun sens :
            // on ouvre le suivi de commande, jamais la publicité.
            'notify_order_updates' => true,
        ])->save();

        $user->emailVerificationCodes()->delete();
    }

    /** Coupe tous les envois et oublie l'adresse confirmée. */
    public function disable(User $user): void
    {
        $user->forceFill([
            'notification_email' => null,
            'notification_email_verified_at' => null,
            'notify_order_updates' => false,
            'notify_promotions' => false,
        ])->save();

        $user->emailVerificationCodes()->delete();
    }

    /**
     * Raison de l'échec, formulée pour le client.
     *
     * Le détail technique part dans les journaux ; l'écran ne montre que ce
     * qui aide à décider quoi faire.
     */
    private function deliveryFailureMessage(Throwable $e): string
    {
        $previous = $e->getPrevious();
        $detail = $previous?->getMessage() ?? $e->getMessage();

        if (str_contains($detail, 'certificat') || str_contains($detail, 'certificate')) {
            return 'Envoi impossible : la configuration TLS du serveur est incomplète. '
                .'Prévenez l’administrateur du site.';
        }

        return 'Impossible d’envoyer le code pour le moment. Réessayez dans quelques instants.';
    }

    /** Code en attente pour cette adresse, s'il en existe un. */
    private function pendingFor(User $user, string $email): ?EmailVerificationCode
    {
        return $user->emailVerificationCodes()
            ->where('email', $email)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /** Six chiffres, zéros de tête compris. */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
