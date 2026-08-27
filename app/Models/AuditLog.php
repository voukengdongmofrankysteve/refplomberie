<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Une ligne du journal d'audit : qui a fait quoi, sur quoi, et quand.
 *
 * N'existe que pour les actions d'un membre du personnel authentifié
 * (vendeur, gestionnaire de stock, administrateur) — un client qui passe
 * commande ou modifie son propre profil n'a rien d'un changement à
 * surveiller. La ligne ne se modifie ni ne se supprime jamais après coup :
 * un journal qu'on peut corriger n'en est plus un.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $snapshot
 * @property array<string, array{old: mixed, new: mixed}>|null $changes
 * @property Carbon $created_at
 */
#[Fillable(['user_id', 'action', 'auditable_type', 'auditable_id', 'snapshot', 'changes'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Enregistre une ligne, si — et seulement si — un membre du personnel
     * est authentifié au moment de l'action.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    public static function record(
        Model $subject,
        string $action,
        ?array $snapshot = null,
        ?array $changes = null,
    ): void {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isStaff()) {
            return;
        }

        static::create([
            'user_id' => $user->id,
            'action' => $action,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'snapshot' => $snapshot,
            'changes' => $changes,
        ]);
    }
}
