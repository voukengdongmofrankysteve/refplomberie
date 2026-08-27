<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Témoignage client affiché sur la vitrine — saisi par l'équipe, pas généré
 * par les clients : contrairement à un avis produit, rien ne le rattache à
 * un achat précis.
 *
 * @property int $id
 * @property string $name
 * @property string|null $role
 * @property string $text
 * @property int $rating
 * @property int $position
 * @property bool $is_active
 */
#[Fillable(['name', 'role', 'text', 'rating', 'position', 'is_active'])]
class Testimonial extends Model
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
     * @param  Builder<Testimonial>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Initiales affichées dans l'avatar, faute de photo : première lettre
     * du prénom et du nom, comme « Marc Dupont » devient « MD ».
     */
    public function initials(): string
    {
        $letters = collect(explode(' ', trim($this->name)))
            ->filter()
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->take(2)
            ->implode('');

        return $letters !== '' ? $letters : '?';
    }
}
