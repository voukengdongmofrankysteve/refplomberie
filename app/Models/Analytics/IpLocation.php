<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Résultat de localisation mémorisé pour une empreinte d'adresse IP.
 *
 * Sans cette mémoire, chaque page rechargée redemanderait la même réponse au
 * fournisseur, qui finirait par nous limiter.
 *
 * @property int $id
 * @property string $ip_hash
 * @property string|null $continent_code
 * @property string|null $continent
 * @property string|null $country_code
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $timezone
 * @property bool $resolved
 */
#[Fillable([
    'ip_hash',
    'continent_code',
    'continent',
    'country_code',
    'country',
    'region',
    'city',
    'timezone',
    'resolved',
])]
class IpLocation extends Model
{
    protected $table = 'analytics_ip_locations';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['resolved' => 'boolean'];
    }

    /**
     * Champs géographiques seuls, prêts à recopier sur une visite.
     *
     * @return array<string, string|null>
     */
    public function place(): array
    {
        return $this->only([
            'continent_code',
            'continent',
            'country_code',
            'country',
            'region',
            'city',
            'timezone',
        ]);
    }
}
