<?php

namespace App\Services\Analytics;

use App\Models\Analytics\IpLocation;
use GeoIp2\Database\Reader;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Situe un visiteur sur la carte à partir de son adresse IP.
 *
 * Deux fournisseurs, tous deux gratuits, choisis dans cet ordre :
 *
 *  1. une base GeoLite2 posée sur le serveur — hors ligne, sans limite de
 *     débit, et rien ne sort de la machine ;
 *  2. à défaut, l'API publique ip-api.com, qui ne demande aucune inscription.
 *
 * Chaque adresse n'est interrogée qu'une seule fois : le résultat, positif ou
 * négatif, est mémorisé dans analytics_ip_locations. Un site qui reçoit mille
 * pages par jour depuis cent adresses fait cent appels, pas mille.
 */
class Geolocator
{
    /**
     * Localise une adresse, en repassant par la mémoire quand elle y est.
     *
     * Renvoie un tableau vide quand la localisation échoue : une visite sans
     * pays reste une visite, il n'y a pas de raison de la perdre.
     *
     * @return array<string, string|null>
     */
    public function locate(string $ip, string $ipHash): array
    {
        $known = IpLocation::where('ip_hash', $ipHash)->first();

        if ($known !== null) {
            return $known->resolved ? $known->place() : [];
        }

        $place = $this->reserved($ip) ? [] : $this->lookup($ip);

        // Les adresses privées et les échecs sont mémorisés eux aussi, sinon
        // on réinterrogerait le fournisseur à chaque visite d'un bureau dont
        // le réseau local n'est pas localisable.
        IpLocation::create([
            'ip_hash' => $ipHash,
            'resolved' => $place !== [],
            ...$place,
        ]);

        return $place;
    }

    /**
     * Fournisseur réellement utilisé, pour l'afficher au responsable.
     */
    public function driver(): string
    {
        $configured = (string) config('analytics.geo.driver', 'auto');

        if ($configured !== 'auto') {
            return $configured;
        }

        return $this->databasePath() !== null ? 'maxmind' : 'http';
    }

    /**
     * @return array<string, string|null>
     */
    private function lookup(string $ip): array
    {
        $driver = $this->driver();

        try {
            return match ($driver) {
                'maxmind' => $this->fromDatabase($ip),
                'http' => $this->fromApi($ip),
                default => [],
            };
        } catch (ConnectionException $e) {
            // Fournisseur injoignable : on ne bloque pas la mesure pour ça.
            Log::warning('Localisation indisponible : '.$e->getMessage());

            return [];
        } catch (Throwable $e) {
            Log::warning('Localisation impossible : '.$e->getMessage());

            return [];
        }
    }

    /**
     * Lecture de la base GeoLite2 locale.
     *
     * @return array<string, string|null>
     */
    private function fromDatabase(string $ip): array
    {
        $path = $this->databasePath();

        if ($path === null || ! class_exists(Reader::class)) {
            // Le pilote a été forcé sans que le nécessaire soit installé :
            // on bascule sur l'API plutôt que de ne rien mesurer.
            return $this->fromApi($ip);
        }

        $reader = new Reader($path);
        $record = $reader->city($ip);

        return [
            'continent_code' => $record->continent->code,
            'continent' => $record->continent->name,
            'country_code' => $record->country->isoCode,
            'country' => $record->country->name,
            'region' => $record->mostSpecificSubdivision->name,
            'city' => $record->city->name,
            'timezone' => $record->location->timeZone,
        ];
    }

    /**
     * Interrogation d'ip-api.com.
     *
     * La liste de champs est explicite : sans elle l'API renvoie une quinzaine
     * d'informations dont nous n'avons que faire, dont les coordonnées exactes
     * et le fournisseur d'accès.
     *
     * @return array<string, string|null>
     */
    private function fromApi(string $ip): array
    {
        $response = Http::timeout((int) config('analytics.geo.timeout', 4))
            ->get(rtrim((string) config('analytics.geo.endpoint'), '/').'/'.$ip, [
                'fields' => 'status,continent,continentCode,country,countryCode,regionName,city,timezone',
                'lang' => 'fr',
            ]);

        if ($response->failed() || $response->json('status') !== 'success') {
            return [];
        }

        return [
            'continent_code' => $response->json('continentCode'),
            'continent' => $response->json('continent'),
            'country_code' => $response->json('countryCode'),
            'country' => $response->json('country'),
            'region' => $response->json('regionName'),
            'city' => $response->json('city'),
            'timezone' => $response->json('timezone'),
        ];
    }

    private function databasePath(): ?string
    {
        $path = (string) config('analytics.geo.database');

        return $path !== '' && is_file($path) ? $path : null;
    }

    /**
     * Adresse locale ou privée : le développement, et les réseaux internes.
     */
    private function reserved(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
