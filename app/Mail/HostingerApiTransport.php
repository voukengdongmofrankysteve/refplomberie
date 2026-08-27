<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport Laravel adossé à l'API Mail de Hostinger.
 *
 * L'API remplace le SMTP : un jeton porteur, un appel HTTPS, aucun port
 * sortant à ouvrir sur l'hébergement mutualisé. Comme c'est un transport
 * Symfony Mailer classique, tout le reste du code continue d'utiliser
 * `Mail::send()` et les notifications Laravel sans rien savoir de Hostinger.
 *
 * @see https://api.mail.hostinger.com
 */
class HostingerApiTransport extends AbstractTransport
{
    /** Le couple compte/boîte change rarement : on évite un aller-retour. */
    private const MAILBOX_CACHE_TTL = 3600;

    public function __construct(
        private readonly string $token,
        private readonly string $mailbox,
        private readonly string $baseUrl = 'https://api.mail.hostinger.com',
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'hostinger://'.$this->mailbox;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $payload = array_filter([
            'to' => $this->addresses($email->getTo()),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'displayName' => $this->displayName($email),
            'attachments' => $this->attachments($email),
        ], fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                // Un pic de latence côté API ne doit pas perdre l'email.
                ->retry(2, 500, throw: false)
                ->post($this->sendUrl(), $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException($this->unreachable($e), previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Envoi Hostinger refusé ('.$response->status().') : '
                .($response->json('error') ?? $response->body()),
            );
        }
    }

    /** URL d'envoi, identifiant de boîte résolu. */
    private function sendUrl(): string
    {
        return $this->baseUrl.'/api/v1/mailboxes/'
            .rawurlencode($this->mailboxResourceId()).'/send';
    }

    /**
     * Identifiant technique de la boîte, déduit de son adresse.
     *
     * On ne demande à la configuration que l'adresse, plus lisible et plus
     * stable qu'un identifiant opaque ; l'API fait la correspondance.
     */
    private function mailboxResourceId(): string
    {
        return Cache::remember(
            'hostinger-mailbox:'.md5($this->token.$this->mailbox),
            self::MAILBOX_CACHE_TTL,
            function (): string {
                try {
                    $response = Http::withToken($this->token)
                        ->acceptJson()
                        ->timeout(15)
                        ->get($this->baseUrl.'/api/v1/me');
                } catch (ConnectionException $e) {
                    throw new RuntimeException($this->unreachable($e), previous: $e);
                }

                if ($response->failed()) {
                    throw new RuntimeException(
                        'Compte Hostinger inaccessible ('.$response->status().') : '
                        .($response->json('error') ?? 'jeton invalide ?'),
                    );
                }

                foreach ($response->json('data.mailboxes', []) as $mailbox) {
                    if (strcasecmp($mailbox['address'] ?? '', $this->mailbox) === 0) {
                        return $mailbox['resourceId'];
                    }
                }

                throw new RuntimeException(
                    "La boîte « {$this->mailbox} » n'est pas accessible avec ce jeton.",
                );
            },
        );
    }

    /**
     * Message d'échec réseau, orienté vers la cause la plus probable.
     *
     * Une erreur de certificat ne vient presque jamais de l'API : c'est le
     * PHP local qui n'a pas de bundle de certificats racine configuré.
     */
    private function unreachable(ConnectionException $e): string
    {
        if (str_contains($e->getMessage(), 'certificate')) {
            return 'Serveur mail injoignable : PHP ne peut pas vérifier le '
                .'certificat TLS. Renseignez curl.cainfo et openssl.cafile '
                .'dans php.ini avec un bundle de certificats racine.';
        }

        return 'Serveur mail injoignable pour le moment. Réessayez dans un instant.';
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): string => $address->getAddress(), $addresses);
    }

    /** Nom affiché à la réception, tiré de l'expéditeur du message. */
    private function displayName(Email $email): ?string
    {
        $from = $email->getFrom()[0] ?? null;

        return $from?->getName() !== '' ? $from?->getName() : null;
    }

    /**
     * Pièces jointes, y compris les images intégrées repérées par leur `cid`.
     *
     * @return array<int, array<string, string>>
     */
    private function attachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $entry = [
                'filename' => $attachment->getFilename() ?? 'piece-jointe',
                'content' => base64_encode($attachment->getBody()),
                'contentType' => $attachment->getContentType(),
                'encoding' => 'base64',
            ];

            // Une image référencée par <img src="cid:..."> voyage en `inline`.
            if ($disposition === 'inline') {
                $entry['cid'] = trim((string) $headers->getHeaderBody('Content-ID'), '<>');
            }

            $attachments[] = $entry;
        }

        return $attachments;
    }
}
