<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Code à usage unique confirmant l'adresse de notification d'un client.
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string $code_hash
 * @property int $attempts
 * @property Carbon $expires_at
 */
#[Fillable(['user_id', 'email', 'code_hash', 'attempts', 'expires_at'])]
class EmailVerificationCode extends Model
{
    /** Au-delà, on invalide le code : inutile de laisser deviner 6 chiffres. */
    public const MAX_ATTEMPTS = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function matches(string $code): bool
    {
        return Hash::check(trim($code), $this->code_hash);
    }
}
