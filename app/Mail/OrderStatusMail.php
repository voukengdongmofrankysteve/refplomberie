<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avancement d'une commande annoncé au client.
 */
class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectFor($this->order->status).' — '.$this->order->reference,
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('items');

        return new Content(
            view: 'emails.order-status',
            with: [
                'order' => $this->order,
                'headline' => $this->headlineFor($this->order->status),
                'store' => StoreSetting::current(),
                'ordersUrl' => route('account.orders'),
                'preferencesUrl' => route('profile.edit'),
                'money' => fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA',
            ],
        );
    }

    /** Objet du message, adapté à l'étape atteinte. */
    private function subjectFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'Commande bien reçue',
            OrderStatus::Confirmed => 'Commande confirmée',
            OrderStatus::Preparing => 'Commande en préparation',
            OrderStatus::Shipped => 'Commande expédiée',
            OrderStatus::Delivered => 'Commande livrée',
            OrderStatus::Cancelled => 'Commande annulée',
        };
    }

    /** Phrase d'accroche : ce que le client doit comprendre en un coup d'œil. */
    private function headlineFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'Nous avons bien reçu votre commande et la traitons au plus vite.',
            OrderStatus::Confirmed => 'Votre commande est confirmée. Nous préparons vos articles.',
            OrderStatus::Preparing => 'Vos articles sont en cours de préparation dans notre dépôt.',
            OrderStatus::Shipped => 'Votre commande est en route. Notre livreur vous contactera au numéro indiqué.',
            OrderStatus::Delivered => 'Votre commande a été livrée. Merci de votre confiance !',
            OrderStatus::Cancelled => 'Votre commande a été annulée. Contactez-nous si cela vous semble être une erreur.',
        };
    }
}
