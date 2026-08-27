<?php

namespace App\Models\Analytics;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un navigateur, ou une installation de l'application mobile.
 *
 * Le même être humain sur son téléphone puis son ordinateur compte pour deux :
 * aucune mesure d'audience ne sait faire autrement sans pister les gens à
 * travers leurs appareils, ce que nous ne faisons pas.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property int $sessions_count
 * @property int $events_count
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 */
#[Fillable([
    'uuid',
    'user_id',
    'sessions_count',
    'events_count',
    'first_seen_at',
    'last_seen_at',
])]
class Visitor extends Model
{
    protected $table = 'analytics_visitors';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Session, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'visitor_id');
    }
}
