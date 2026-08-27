<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Le code est figé sur la commande : le supprimer plus tard ne doit
            // pas réécrire l'historique de ce qui a été facturé.
            $table->string('promo_code')->nullable()->after('shipping');
            $table->unsignedInteger('discount')->default(0)->after('promo_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['promo_code', 'discount']);
        });
    }
};
