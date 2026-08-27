<?php

use App\Enums\PromoCodeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            // Saisi par le client en majuscules : on normalise à l'écriture.
            $table->string('code')->unique();
            $table->string('label')->nullable();
            $table->string('type')->default(PromoCodeType::Percent->value);
            // Pourcentage (1–100) ou montant en francs CFA, selon le type.
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_subtotal')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
