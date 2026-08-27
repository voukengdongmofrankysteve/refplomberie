<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée (ou remet à niveau) le compte administrateur.
 *
 * C'est le seul moyen d'obtenir un accès au back-office : aucune inscription
 * publique ne peut produire un compte administrateur. Les identifiants sont
 * pilotés par l'environnement afin de ne pas figer un mot de passe en clair
 * dans le dépôt.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('shop.admin.email');
        $password = (string) config('shop.admin.password');

        $admin = User::withoutGlobalScopes()->firstOrNew(['email' => $email]);

        $admin->name = (string) config('shop.admin.name');
        $admin->role = UserRole::Admin;
        $admin->email_verified_at ??= now();

        // Le mot de passe n'est (ré)écrit qu'à la création : un admin qui a
        // changé son mot de passe ne le voit pas réinitialisé au prochain seed.
        if (! $admin->exists) {
            $admin->password = Hash::make($password);
        }

        $admin->save();

        $this->command?->info("Compte administrateur disponible : {$email}");
    }
}
