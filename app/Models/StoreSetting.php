<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Réglages de la boutique — une unique ligne éditée depuis le back-office.
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $phone
 * @property string $whatsapp
 * @property string $email
 * @property string $hours
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $map_zoom
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $og_image
 * @property string|null $google_site_verification
 * @property bool $is_indexable
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property string|null $linkedin_url
 */
#[Fillable([
    'name',
    'address',
    'phone',
    'whatsapp',
    'email',
    'hours',
    'latitude',
    'longitude',
    'map_zoom',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'og_image',
    'google_site_verification',
    'is_indexable',
    'facebook_url',
    'instagram_url',
    'linkedin_url',
])]
class StoreSetting extends Model
{
    use Auditable;

    /** Mémoïsation par requête : ces réglages sont lus à chaque page. */
    private static ?self $current = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_indexable' => 'boolean',
        ];
    }

    /**
     * Réglages courants, avec repli sur les valeurs de `config/shop.php`
     * tant que la table n'a pas été alimentée.
     */
    public static function current(): self
    {
        return self::$current ??= self::query()->first() ?? new self([
            ...config('shop.store'),
            'map_zoom' => 15,
        ]);
    }

    /** Invalide le cache mémoire après une modification. */
    public static function forgetCurrent(): void
    {
        self::$current = null;
    }

    /**
     * Requête transmise à Google Maps : les coordonnées GPS l'emportent sur
     * l'adresse postale, qui reste le repli.
     */
    public function mapQuery(): string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return $this->latitude.','.$this->longitude;
        }

        return $this->address;
    }

    /** URL de la carte intégrée (aucune clé d'API requise). */
    public function mapEmbedUrl(): string
    {
        return 'https://maps.google.com/maps?q='.rawurlencode($this->mapQuery())
            .'&z='.$this->map_zoom
            .'&hl=fr&ie=UTF8&iwloc=&output=embed';
    }

    /** Lien « Ouvrir dans Google Maps ». */
    public function mapLinkUrl(): string
    {
        return 'https://maps.google.com/?q='.rawurlencode($this->mapQuery());
    }

    /** Image de partage par défaut, en URL absolue. */
    public function shareImageUrl(): string
    {
        return ProductImageService::absoluteUrl($this->og_image)
            ?? url('/og-image.png');
    }

    /**
     * Charge utile partagée à toutes les pages Inertia.
     *
     * @return array<string, mixed>
     */
    public function toSharedArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'hours' => $this->hours,
            'mapEmbedUrl' => $this->mapEmbedUrl(),
            'mapLinkUrl' => $this->mapLinkUrl(),
            'shippingCost' => (int) config('shop.shipping.cost'),
            'freeShippingFrom' => (int) config('shop.shipping.free_from'),
        ];
    }

    /**
     * Profils sociaux renseignés, pour le `sameAs` des données structurées.
     *
     * @return array<int, string>
     */
    public function socialProfiles(): array
    {
        return array_values(array_filter([
            $this->facebook_url,
            $this->instagram_url,
            $this->linkedin_url,
        ]));
    }
}
