<?php

namespace App\Notifications;

use App\Models\Campaign;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Annonce promotionnelle poussée aux clients abonnés.
 */
class CampaignNotification extends Notification
{
    public function __construct(public readonly Campaign $campaign) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'campaign',
            'title' => $this->campaign->subject,
            'body' => $this->preview(),
            'campaignId' => $this->campaign->id,
            'promoCode' => $this->campaign->promo_code,
            'url' => '/',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $cards = $this->campaign->productCards();

        return [
            'title' => $this->campaign->subject,
            'body' => $this->preview(),
            // Une vignette rend la bannière nettement plus cliquable.
            'image' => $cards[0]['image'] ?? null,
            'data' => array_filter([
                'type' => 'campaign',
                'campaignId' => (string) $this->campaign->id,
                'promoCode' => $this->campaign->promo_code,
                'url' => '/',
            ]),
        ];
    }

    /**
     * Première phrase du message, tronquée : une bannière push n'affiche que
     * deux lignes, inutile d'y verser tout le corps de la campagne.
     */
    private function preview(): string
    {
        $paragraphs = $this->campaign->paragraphs();

        return Str::limit($paragraphs[0] ?? $this->campaign->subject, 120);
    }
}
