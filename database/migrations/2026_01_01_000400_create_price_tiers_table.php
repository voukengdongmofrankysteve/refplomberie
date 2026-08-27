<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            // `null` = palier ouvert, sans limite haute.
            $table->unsignedInteger('max_qty')->nullable();
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->index(['product_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
