<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Seuil de réapprovisionnement : au-dessous, le produit remonte
            // dans l'alerte du tableau de bord. 0 désactive la surveillance.
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('low_stock_threshold');
        });
    }
};
