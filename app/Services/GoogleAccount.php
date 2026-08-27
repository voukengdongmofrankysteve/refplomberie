<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Retrouve — ou crée — le compte client derrière une identité Google.
 *
 * Partagé par le site et l'application mobile : les deux arrivent par des
 * chemins différents (redirection OAuth d'un côté, jeton d'identité de
 * l'autre) mais aboutissent aux mêmes règles de rattachement.
 *
 * Réservé aux clients : l'administration se gère depuis une session, avec un
 * mot de passe, jamais depuis un compte Google. Le contrôle porte ici, au
 * point de passage unique, plutôt que dans chaque contrôleur — un compte
 * administrateur qui aurait un jour un `google_id` resterait ainsi bloqué à
 * chaque tentative, pas seulement à la création.
 */
class GoogleAccount
{
    /**
     * @param  string  $googleId  Identifiant Google, stable et définitif.
     * @param  bool  $emailVerified  Google atteste-t-il de cette adresse ?
     *
     * @throws RuntimeException Si le compte trouvé est celui d'un administrateur.
     */
    public function resolve(
        string $googleId,
        ?string $email,
        ?string $name,
        ?string $avatar = null,
        bool $emailVerified = true,
    ): User {
        $existing = User::where('google_id', $googleId)->first();

        if ($existing !== null) {
            $this->rejectAdmin($existing);

            return $this->refresh($existing, $name, $avatar);
        }

        if (blank($email)) {
            // Sans adresse, impossible de rattacher le compte ni de lui
            // écrire. Google la fournit toujours avec le périmètre demandé :
            // arriver ici signale un problème de configuration.
            throw new RuntimeException(
                'Google n’a pas transmis d’adresse email pour ce compte.',
            );
        }

        $byEmail = User::where('email', $email)->first();

        if ($byEmail !== null) {
            $this->rejectAdmin($byEmail);

            // Rattachement d'un compte existant. Conditionné à la vérification
            // par Google : sans elle, il suffirait de déclarer l'adresse d'un
            // client pour prendre la main sur son compte.
            if (! $emailVerified) {
                throw new RuntimeException(
                    'Cette adresse est déjà utilisée. Connectez-vous avec votre '
                    .'mot de passe, puis liez Google depuis vos réglages.',
                );
            }

            $byEmail->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $byEmail->avatar_url ?? $avatar,
                // Google a vérifié l'adresse : inutile de la faire confirmer
                // une seconde fois par email.
                'email_verified_at' => $byEmail->email_verified_at ?? Carbon::now(),
            ])->save();

            return $byEmail;
        }

        // Nouveau client. Aucun mot de passe n'est posé : il pourra en
        // définir un plus tard via « mot de passe oublié » s'il veut aussi
        // pouvoir se connecter sans Google.
        $user = new User([
            'name' => $name ?: 'Client',
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatar,
            'password' => null,
        ]);

        // Forcé plutôt que rempli en masse : `email_verified_at` n'est pas —
        // et ne doit pas devenir — assignable depuis une requête.
        $user->forceFill([
            'email_verified_at' => $emailVerified ? Carbon::now() : null,
        ])->save();

        return $user;
    }

    /**
     * Refuse la connexion Google si le compte visé est celui d'un administrateur.
     *
     * @throws RuntimeException
     */
    private function rejectAdmin(User $user): void
    {
        if ($user->isStaff()) {
            throw new RuntimeException(
                'L’administration se gère depuis le site, avec votre mot de passe.',
            );
        }
    }

    /**
     * Rafraîchit ce que Google sait mieux que nous : la photo, et le nom si
     * le client n'en a jamais saisi un.
     */
    private function refresh(User $user, ?string $name, ?string $avatar): User
    {
        $changes = array_filter([
            'avatar_url' => $avatar,
            'name' => blank($user->name) ? $name : null,
        ]);

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }

        return $user;
    }
}
