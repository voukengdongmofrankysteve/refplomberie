<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Identifiant Google du compte, stable même si le client change
            // d'adresse : c'est lui, et non l'email, qui fait foi au retour.
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');

            // Un compte créé par Google n'a pas de mot de passe, et n'en aura
            // jamais s'il ne le demande pas. La colonne devient donc facultative.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['google_id', 'avatar_url']);
        });

        // Les comptes sans mot de passe empêcheraient de rendre la colonne
        // obligatoire : on leur en donne un, inutilisable, avant de revenir
        // en arrière.
        User::whereNull('password')
            ->update(['password' => bcrypt(Str::random(64))]);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });
    }
};
