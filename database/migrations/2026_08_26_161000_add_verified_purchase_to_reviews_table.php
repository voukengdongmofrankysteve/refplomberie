<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            // Faux par défaut : les avis déjà en base n'ont jamais été
            // rattachés à une commande et ne peuvent pas être vérifiés
            // rétroactivement.
            $table->boolean('verified_purchase')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn('verified_purchase');
        });
    }
};
