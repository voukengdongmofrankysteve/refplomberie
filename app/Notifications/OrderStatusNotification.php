<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/**
 * Avancement d'une commande, annoncé au client.
 *
 * Toujours consignée en base — c'est le journal que le client retrouve dans
 * l'application, et il ne peut pas le couper. Le push, lui, dépend de ses
 * préférences et de ses appareils enregistrés.
 */
class OrderStatusNotification extends Notification
{
    public function __construct(public readonly Order $order) {}

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
            'type' => 'order',
            'title' => $this->title(),
            'body' => $this->body(),
            'reference' => $this->order->reference,
            'orderId' => $this->order->id,
            'status' => $this->order->status->value,
            'url' => '/mes-commandes',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'data' => [
                'type' => 'order',
                'orderId' => (string) $this->order->id,
                'reference' => $this->order->reference,
                'url' => '/mes-commandes',
            ],
        ];
    }

    private function title(): string
    {
        return match ($this->order->status) {
            OrderStatus::Pending => 'Commande bien reçue',
            OrderStatus::Confirmed => 'Commande confirmée',
            OrderStatus::Preparing => 'Commande en préparation',
            OrderStatus::Shipped => 'Commande expédiée',
            OrderStatus::Delivered => 'Commande livrée',
            OrderStatus::Cancelled => 'Commande annulée',
        };
    }

    private function body(): string
    {
        $reference = $this->order->reference;

        return match ($this->order->status) {
            OrderStatus::Pending => "Nous traitons votre commande {$reference}.",
            OrderStatus::Confirmed => "Votre commande {$reference} est confirmée.",
            OrderStatus::Preparing => "Nous préparons votre commande {$reference}.",
            OrderStatus::Shipped => "Votre commande {$reference} est en route !",
            OrderStatus::Delivered => "Votre commande {$reference} a été livrée. Merci !",
            OrderStatus::Cancelled => "Votre commande {$reference} a été annulée.",
        };
    }
}
