<?php

namespace App\Models\Analytics;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Une visite : la suite ininterrompue des actions d'un visiteur.
 *
 * @property int $id
 * @property int $visitor_id
 * @property int|null $user_id
 * @property string|null $ip_hash
 * @property string|null $continent_code
 * @property string|null $continent
 * @property string|null $country_code
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $timezone
 * @property string $source
 * @property string|null $device
 * @property string|null $platform
 * @property string|null $browser
 * @property string|null $referrer_host
 * @property string|null $referrer
 * @property string|null $landing_path
 * @property int $page_views
 * @property int $events_count
 * @property Carbon $started_at
 * @property Carbon $last_activity_at
 */
#[Fillable([
    'visitor_id',
    'user_id',
    'ip_hash',
    'continent_code',
    'continent',
    'country_code',
    'country',
    'region',
    'city',
    'timezone',
    'source',
    'device',
    'platform',
    'browser',
    'referrer_host',
    'referrer',
    'landing_path',
    'page_views',
    'events_count',
    'started_at',
    'last_activity_at',
])]
class Session extends Model
{
    protected $table = 'analytics_sessions';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Visitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'session_id');
    }
}
