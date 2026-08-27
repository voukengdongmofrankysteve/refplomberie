<?php

namespace App\Models\Analytics;

use App\Enums\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Une action mesurée : page vue, fiche consultée, commande passée.
 *
 * @property int $id
 * @property int|null $session_id
 * @property int|null $visitor_id
 * @property int|null $user_id
 * @property AnalyticsEvent $type
 * @property string|null $path
 * @property string|null $title
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $label
 * @property int|null $value
 * @property array<string, mixed>|null $meta
 * @property Carbon $occurred_at
 */
#[Fillable([
    'session_id',
    'visitor_id',
    'user_id',
    'type',
    'path',
    'title',
    'subject_type',
    'subject_id',
    'label',
    'value',
    'meta',
    'occurred_at',
])]
class Event extends Model
{
    protected $table = 'analytics_events';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnalyticsEvent::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Restreint à une fenêtre de dates, bornes comprises.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeBetweenDates(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('occurred_at', [$from, $to]);
    }

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
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
     * Cible facultative de l'action : le produit consulté, la commande passée.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
