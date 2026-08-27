<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Campagne promotionnelle envoyée aux clients qui l'ont demandée.
 */
class PromotionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promotion',
            with: [
                'subject' => $this->campaign->subject,
                'name' => $this->recipient->name,
                // Les sauts de ligne saisis dans le back-office deviennent des
                // paragraphes : l'admin écrit du texte, pas du HTML.
                'paragraphs' => $this->campaign->paragraphs(),
                'products' => $this->campaign->productCards(),
                'promoCode' => $this->campaign->promo_code,
                'store' => StoreSetting::current(),
                'shopUrl' => route('home'),
                'preferencesUrl' => route('profile.edit'),
                'money' => fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA',
            ],
        );
    }
}
