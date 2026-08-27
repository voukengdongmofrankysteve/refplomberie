<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Prix propre à la vente, indépendant du prix barré permanent du
            // produit : la vente flash termine, le catalogue redevient lui-même.
            $table->unsignedInteger('sale_price');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['flash_sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_products');
    }
};
