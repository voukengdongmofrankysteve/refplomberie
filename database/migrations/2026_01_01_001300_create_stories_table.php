<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fil de statuts façon réseaux sociaux, mais déroulé horizontalement sur
     * la page d'accueil : photos d'arrivage, chantiers, courtes vidéos.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('caption')->nullable();

            // 'image' ou 'video' — la vidéo s'appuie sur `poster` pour sa vignette.
            $table->string('media_type')->default('image');
            $table->string('media_path');
            $table->string('poster_path')->nullable();

            // Lien facultatif : fiche produit, ancre de la vitrine, page externe.
            $table->string('link_url')->nullable();
            $table->string('link_label')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
