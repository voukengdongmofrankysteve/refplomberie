<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property UserRole $role
 * @property string|null $phone
 * @property string|null $address
 * @property Carbon|null $email_verified_at
 * @property string|null $notification_email
 * @property Carbon|null $notification_email_verified_at
 * @property bool $notify_order_updates
 * @property bool $notify_promotions
 * @property bool $notify_push
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'avatar_url',
    'role',
    'phone',
    'address',
    'notification_email',
    'notification_email_verified_at',
    'notify_order_updates',
    'notify_promotions',
    'notify_push',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
// Sans cet ajout, l'accesseur `avatar` existerait côté PHP mais ne partirait
// jamais vers le front, qui l'attend dans les données partagées.
#[Appends(['avatar'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
            'notification_email_verified_at' => 'datetime',
            'notify_order_updates' => 'boolean',
            'notify_promotions' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    /**
     * Photo affichée dans le menu du compte.
     *
     * Exposée sous le nom court `avatar`, celui qu'attend déjà le front. Elle
     * ne vient que de Google : la boutique ne demande pas de photo à
     * l'inscription, et les initiales font l'affaire sans elle.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_url);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Un compte du personnel (vendeur, gestionnaire de stock, admin) — par opposition à un client. */
    public function isStaff(): bool
    {
        // Un compte fraîchement créé sans rôle explicite (valeur par défaut
        // de la colonne, jamais relue en mémoire par Eloquent) n'a rien
        // d'un membre du personnel : `null` se traite comme `Customer`.
        return ($this->role ?? UserRole::Customer)->isStaff();
    }

    /**
     * Nommée `hasPermission` plutôt que `can`, pour ne pas entrer en
     * collision avec `Authorizable::can()` (Gates/Policies Laravel).
     */
    public function hasPermission(Permission $permission): bool
    {
        return ($this->role ?? UserRole::Customer)->can($permission);
    }

    /**
     * Valeurs des permissions du rôle, pour le partage Inertia côté front.
     *
     * @return list<string>
     */
    public function permissionValues(): array
    {
        return array_map(
            fn (Permission $permission): string => $permission->value,
            ($this->role ?? UserRole::Customer)->permissions(),
        );
    }

    /**
     * Adresse à laquelle envoyer les notifications Laravel.
     *
     * Tant que le client n'a pas confirmé d'adresse dédiée, aucune
     * notification ne part : c'est le sens même de l'opt-in.
     *
     * @return array<string, string>|string
     */
    public function routeNotificationForMail(): array|string
    {
        return [$this->notification_email => $this->name];
    }

    /** Le client a-t-il confirmé une adresse par code à usage unique ? */
    public function hasVerifiedNotificationEmail(): bool
    {
        return $this->notification_email !== null
            && $this->notification_email_verified_at !== null;
    }

    /**
     * Le client accepte-t-il ce type d'email ?
     *
     * Le consentement se vérifie toujours en deux temps : une adresse
     * confirmée, puis une case cochée pour ce thème précis.
     */
    public function acceptsEmail(string $topic): bool
    {
        if (! $this->hasVerifiedNotificationEmail()) {
            return false;
        }

        return match ($topic) {
            'orders' => $this->notify_order_updates,
            'promotions' => $this->notify_promotions,
            default => false,
        };
    }

    /**
     * Rend le consentement caduc : toute modification de l'adresse impose une
     * nouvelle confirmation, et coupe les envois en attendant.
     */
    public function resetNotificationEmail(?string $email): void
    {
        $this->forceFill([
            'notification_email' => $email,
            'notification_email_verified_at' => null,
        ])->save();
    }

    /**
     * Le client accepte-t-il les notifications push ?
     *
     * Contrairement aux notifications en base — toujours actives, et que
     * personne ne peut couper — le push est intrusif : il reste un choix.
     */
    public function acceptsPush(): bool
    {
        return $this->notify_push && $this->deviceTokens()->exists();
    }

    /**
     * @return HasMany<DeviceToken, $this>
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * @return HasMany<EmailVerificationCode, $this>
     */
    public function emailVerificationCodes(): HasMany
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    /**
     * Produits mis en favori par le client.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * @return HasMany<TechnicianRequest, $this>
     */
    public function technicianRequests(): HasMany
    {
        return $this->hasMany(TechnicianRequest::class)->latest();
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
