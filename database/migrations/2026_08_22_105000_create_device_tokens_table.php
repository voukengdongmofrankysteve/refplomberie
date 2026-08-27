<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Le jeton FCM identifie une installation, pas un compte : le même
            // téléphone qui change de compte doit changer de propriétaire, d'où
            // l'unicité sur le jeton seul.
            $table->string('token', 512)->unique();
            $table->string('platform', 20);
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });

        Schema::table('users', function (Blueprint $table): void {
            // Les notifications push suivent l'appareil : décocher ici coupe
            // les envois sans avoir à désinstaller l'application.
            $table->boolean('notify_push')->default(true)->after('notify_promotions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('notify_push');
        });

        Schema::dropIfExists('device_tokens');
    }
};
