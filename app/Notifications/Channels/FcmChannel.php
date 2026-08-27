<?php

namespace App\Notifications\Channels;

use App\Services\FirebaseMessaging;
use Illuminate\Notifications\Notification;

/**
 * Canal « push » branché sur le système de notifications de Laravel.
 *
 * Les notifications déclarent `toFcm()` ; ce canal se charge du reste, si bien
 * qu'aucun appelant n'a à connaître Firebase.
 */
class FcmChannel
{
    public function __construct(private readonly FirebaseMessaging $messaging) {}

    /**
     * @return int Nombre d'appareils atteints.
     */
    public function send(object $notifiable, Notification $notification): int
    {
        if (! method_exists($notification, 'toFcm')) {
            return 0;
        }

        // Le destinataire refuse le push : rien ne part, même s'il a des
        // appareils enregistrés.
        if (method_exists($notifiable, 'acceptsPush') && ! $notifiable->acceptsPush()) {
            return 0;
        }

        $message = $notification->toFcm($notifiable);

        return $this->messaging->sendToUser(
            userId: $notifiable->getKey(),
            title: $message['title'],
            body: $message['body'],
            data: $message['data'] ?? [],
            imageUrl: $message['image'] ?? null,
        );
    }
}
