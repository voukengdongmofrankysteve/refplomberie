<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Mail\OrderStatusMail;
use App\Mail\PromotionMail;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use App\Notifications\CampaignNotification;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envois adressés aux clients : suivi de commande et promotions.
 *
 * Un seul endroit décide si un email peut partir. Le consentement s'y vérifie
 * systématiquement, de sorte qu'aucun appelant ne puisse écrire à quelqu'un
 * qui ne l'a pas demandé.
 */
class CustomerNotifier
{
    /**
     * Prévient le client que sa commande a changé d'étape.
     *
     * Renvoie `true` si un email est effectivement parti — le back-office
     * l'affiche, pour que l'administrateur sache s'il doit relayer sur
     * WhatsApp.
     */
    public function orderStatusChanged(Order $order): bool
    {
        $user = $order->user;

        if ($user === null) {
            return false;
        }

        // Notification en base et push : la première est le journal que le
        // client retrouve dans l'application, et personne ne peut la couper ;
        // le second ne part que vers ses appareils enregistrés.
        try {
            $user->notify(new OrderStatusNotification($order));
        } catch (\Throwable $e) {
            Log::warning('Notification de commande non déposée', [
                'order' => $order->reference,
                'message' => $e->getMessage(),
            ]);
        }

        if (! $user->acceptsEmail('orders')) {
            return false;
        }

        try {
            Mail::to($user->notification_email, $user->name)
                ->send(new OrderStatusMail($order));
        } catch (\Throwable $e) {
            // Un incident chez le fournisseur d'email ne doit jamais empêcher
            // l'administrateur d'enregistrer le nouveau statut.
            Log::warning('Notification de commande non envoyée', [
                'order' => $order->reference,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Diffuse une campagne aux abonnés aux promotions.
     *
     * Le traitement se fait par lots : la liste peut grossir sans que la
     * requête d'envoi ne charge tous les comptes en mémoire.
     */
    public function sendCampaign(Campaign $campaign): int
    {
        $campaign->update(['status' => CampaignStatus::Sending]);

        $sent = 0;
        $pushed = 0;

        if ($campaign->usesChannel('email')) {
            User::query()
                ->whereNotNull('notification_email')
                ->whereNotNull('notification_email_verified_at')
                ->where('notify_promotions', true)
                ->chunkById(100, function ($users) use ($campaign, &$sent): void {
                    foreach ($users as $user) {
                        try {
                            Mail::to($user->notification_email, $user->name)
                                ->send(new PromotionMail($campaign, $user));
                            $sent++;
                        } catch (\Throwable $e) {
                            // Une adresse morte n'interrompt pas la diffusion.
                            Log::warning('Campagne non remise', [
                                'campaign' => $campaign->id,
                                'user' => $user->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        if ($campaign->usesChannel('push')) {
            $pushed = $this->pushCampaign($campaign);
        }

        $campaign->update([
            'status' => CampaignStatus::Sent,
            'recipients_count' => $sent,
            'pushed_count' => $pushed,
            'sent_at' => now(),
        ]);

        return $sent + $pushed;
    }

    /**
     * Dépose la campagne en base pour tous les abonnés, et la pousse à ceux
     * qui ont un appareil.
     *
     * La notification en base part quoi qu'il arrive : c'est le journal
     * consultable dans l'application, que le client ne peut pas désactiver.
     */
    private function pushCampaign(Campaign $campaign): int
    {
        $pushed = 0;

        User::query()
            ->where('notify_promotions', true)
            ->chunkById(100, function ($users) use ($campaign, &$pushed): void {
                foreach ($users as $user) {
                    try {
                        $user->notify(new CampaignNotification($campaign));

                        if ($user->acceptsPush()) {
                            $pushed++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Campagne non poussée', [
                            'campaign' => $campaign->id,
                            'user' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $pushed;
    }

    /** Nombre de clients qui recevraient une notification push maintenant. */
    public function pushAudience(): int
    {
        return User::query()
            ->where('notify_promotions', true)
            ->where('notify_push', true)
            ->whereHas('deviceTokens')
            ->count();
    }

    /** Nombre de clients qui recevraient une campagne envoyée maintenant. */
    public function promotionAudience(): int
    {
        return User::query()
            ->whereNotNull('notification_email')
            ->whereNotNull('notification_email_verified_at')
            ->where('notify_promotions', true)
            ->count();
    }

    /**
     * Message WhatsApp prêt à l'emploi pour annoncer une étape.
     *
     * L'email ne remplace pas WhatsApp : au Cameroun c'est le canal que les
     * clients lisent vraiment, et beaucoup commandent sans créer de compte.
     */
    public function orderStatusWhatsApp(Order $order): string
    {
        $store = StoreSetting::current();

        $lines = [
            "*{$store->name}*",
            '',
            "Bonjour {$order->customer_name},",
            '',
            $this->whatsAppHeadline($order->status),
            '',
            "Référence : *{$order->reference}*",
            'Statut : *'.$order->status->label().'*',
            'Total : *'.number_format($order->total, 0, ',', ' ').' FCFA*',
            '',
            'Merci de votre confiance.',
            '',
            '🔗 '.url('/'),
        ];

        return implode("\n", $lines);
    }

    /**
     * Lien wa.me prêt à cliquer depuis le back-office.
     *
     * Le numéro est réduit à ses chiffres et complété de l'indicatif pays
     * quand le client l'a saisi au format local (6XX XXX XXX).
     */
    public function orderStatusWhatsAppUrl(Order $order): string
    {
        $digits = preg_replace('/\D+/', '', $order->customer_phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '6')) {
            $digits = '237'.$digits;
        }

        return 'https://wa.me/'.$digits
            .'?text='.rawurlencode($this->orderStatusWhatsApp($order));
    }

    private function whatsAppHeadline(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'Nous avons bien reçu votre commande.',
            OrderStatus::Confirmed => 'Votre commande est confirmée, nous la préparons.',
            OrderStatus::Preparing => 'Vos articles sont en cours de préparation.',
            OrderStatus::Shipped => 'Votre commande est en route ! Notre livreur vous appellera.',
            OrderStatus::Delivered => 'Votre commande a bien été livrée.',
            OrderStatus::Cancelled => 'Votre commande a été annulée.',
        };
    }
}
