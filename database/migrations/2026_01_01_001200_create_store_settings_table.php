<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages de la boutique : une seule ligne, éditée depuis le back-office.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();

            // Coordonnées affichées sur la vitrine.
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('email');
            $table->string('hours');

            // Localisation Google Maps : les coordonnées priment sur l'adresse.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('map_zoom')->default(15);

            // Référencement.
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->string('google_site_verification')->nullable();
            $table->boolean('is_indexable')->default(true);

            // Profils sociaux : alimentent le `sameAs` des données structurées.
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
