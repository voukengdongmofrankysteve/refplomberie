<?php

namespace App\Models;

use App\Enums\TechnicianRequestStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference
 * @property int|null $user_id
 * @property int|null $technician_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $address
 * @property string $service
 * @property Carbon|null $preferred_date
 * @property string|null $preferred_time
 * @property string $description
 * @property TechnicianRequestStatus $status
 * @property string|null $admin_note
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference',
    'user_id',
    'technician_id',
    'customer_name',
    'customer_phone',
    'address',
    'service',
    'preferred_date',
    'preferred_time',
    'description',
    'status',
    'admin_note',
])]
class TechnicianRequest extends Model
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
        'status' => TechnicianRequestStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'status' => TechnicianRequestStatus::class,
        ];
    }

    public static function generateReference(): string
    {
        return 'INT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
