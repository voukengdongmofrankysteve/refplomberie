<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Jeton d'appareil Firebase, cible d'une notification push.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $platform
 * @property string|null $device_name
 * @property Carbon|null $last_used_at
 */
#[Fillable(['user_id', 'token', 'platform', 'device_name', 'last_used_at'])]
class DeviceToken extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Enregistre — ou réattribue — un jeton.
     *
     * Un même téléphone peut changer de compte : le jeton est alors repris par
     * le nouvel utilisateur, sinon l'ancien continuerait de recevoir les
     * notifications destinées au nouveau.
     */
    public static function register(
        User $user,
        string $token,
        string $platform,
        ?string $deviceName = null,
    ): self {
        return self::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ],
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
