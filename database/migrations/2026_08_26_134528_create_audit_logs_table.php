<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            // Jamais effacé avec l'auteur : la trace doit survivre au départ
            // d'un administrateur, sinon elle perdrait son sens.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            // Pour une création ou une suppression : l'état complet.
            $table->json('snapshot')->nullable();
            // Pour une modification : {champ: {old, new}} des seuls champs
            // qui ont changé.
            $table->json('changes')->nullable();
            $table->timestamp('created_at');

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
