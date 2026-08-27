<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Question fréquente affichée sur la vitrine.
 *
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property string|null $category
 * @property int $position
 * @property bool $is_active
 */
#[Fillable(['question', 'answer', 'category', 'position', 'is_active'])]
class Faq extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<Faq>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
