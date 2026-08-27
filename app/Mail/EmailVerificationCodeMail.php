<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Code à usage unique confirmant une adresse de notification.
 *
 * Volontairement non différé : le client attend ce code, écran ouvert. Le
 * passer en file d'attente ajouterait une minute de latence, ou rien du tout
 * si aucun worker ne tourne.
 */
class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly int $minutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre code de confirmation : {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}
