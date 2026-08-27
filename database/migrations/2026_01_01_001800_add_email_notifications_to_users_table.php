<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Adresse dédiée aux notifications : distincte de l'identifiant de
            // connexion, et confirmée séparément par code à usage unique.
            $table->string('notification_email')->nullable()->after('email_verified_at');
            $table->timestamp('notification_email_verified_at')->nullable()
                ->after('notification_email');
            // Consentement par thème : suivre sa commande n'engage pas à
            // recevoir de la publicité.
            $table->boolean('notify_order_updates')->default(false)
                ->after('notification_email_verified_at');
            $table->boolean('notify_promotions')->default(false)
                ->after('notify_order_updates');
        });

        Schema::create('email_verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            // Le code n'est jamais stocké en clair : une fuite de base ne doit
            // pas permettre de valider une adresse.
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'notification_email',
                'notification_email_verified_at',
                'notify_order_updates',
                'notify_promotions',
            ]);
        });
    }
};
