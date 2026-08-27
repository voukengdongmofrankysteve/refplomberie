<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un visiteur = un navigateur ou une installation de l'application.
        // Le même être humain sur son téléphone puis son ordinateur compte
        // pour deux : aucune mesure d'audience ne sait faire autrement sans
        // pister les gens à travers les appareils, ce que nous ne faisons pas.
        Schema::create('analytics_visitors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // Dernier compte connu : un visiteur anonyme qui se connecte
            // rattache rétroactivement son parcours.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('events_count')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->index('last_seen_at');
        });

        // Une visite : une suite d'actions sans interruption de plus de
        // trente minutes. C'est l'unité de « combien de fois est-on venu ».
        Schema::create('analytics_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visitor_id')->constrained('analytics_visitors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // L'adresse IP n'est jamais conservée en clair : seule son
            // empreinte permet de reconnaître un même réseau, sans pouvoir
            // remonter à l'abonné.
            $table->string('ip_hash', 64)->nullable()->index();

            $table->string('continent_code', 2)->nullable();
            $table->string('continent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();

            // « web » ou « app » : la vitrine et l'application mobile passent
            // par les mêmes tables, mais on veut pouvoir les distinguer.
            $table->string('source', 16)->default('web');
            $table->string('device', 16)->nullable();
            $table->string('platform', 32)->nullable();
            $table->string('browser', 32)->nullable();

            $table->string('referrer_host')->nullable();
            $table->text('referrer')->nullable();
            $table->string('landing_path')->nullable();

            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('events_count')->default(0);

            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->index('started_at');
            $table->index(['country_code', 'started_at']);
            $table->index('last_activity_at');
        });

        // Le détail : une ligne par action mesurée.
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('analytics_sessions')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('analytics_visitors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 32);
            $table->string('path')->nullable();
            $table->string('title')->nullable();

            // Cible facultative : le produit consulté, le statut regardé, la
            // commande passée. Relation polymorphe non contrainte : on veut
            // garder la statistique même si l'objet est supprimé ensuite.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('label')->nullable();

            // Montant en francs CFA quand l'action en porte un.
            $table->unsignedBigInteger('value')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'type']);
            $table->index('occurred_at');
            $table->index('path');
        });

        // Mémoire des adresses déjà localisées : une adresse n'interroge le
        // fournisseur qu'une seule fois, jamais à chaque page.
        Schema::create('analytics_ip_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_hash', 64)->unique();
            $table->string('continent_code', 2)->nullable();
            $table->string('continent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            // Une adresse privée ou introuvable est mémorisée elle aussi :
            // sans quoi on réessaierait indéfiniment.
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_visitors');
        Schema::dropIfExists('analytics_ip_locations');
    }
};
