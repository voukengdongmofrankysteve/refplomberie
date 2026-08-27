<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $email
 * @property string $phone
 * @property string|null $subject
 * @property string $message
 * @property ContactMessageStatus $status
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id',
    'name',
    'email',
    'phone',
    'subject',
    'message',
    'status',
])]
class ContactMessage extends Model
{
    use Auditable;

    /**
     * Statut initial porté par le modèle.
     *
     * La valeur par défaut de la colonne ne s'applique qu'à l'insertion et
     * laisserait l'instance en mémoire avec un statut nul — ce qui casse
     * toute sérialisation faite juste après la création.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => ContactMessageStatus::New->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
