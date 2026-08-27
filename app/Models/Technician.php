<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $specialty
 * @property string $experience
 * @property float $rating
 * @property int $jobs_count
 * @property string $photo
 * @property bool $is_available
 */
#[Fillable([
    'name',
    'specialty',
    'experience',
    'rating',
    'jobs_count',
    'photo',
    'is_available',
])]
class Technician extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TechnicianRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(TechnicianRequest::class);
    }
}
