<?php

use App\Enums\QuoteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            // Jeton d'accès au PDF : le client n'a pas de compte à créer pour
            // retélécharger son devis, mais la référence seule ne suffit pas.
            $table->string('token', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('customer_company')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('status')->default(QuoteStatus::Draft->value);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('total');
            $table->text('note')->nullable();
            // Un devis engage le vendeur pour une durée limitée.
            $table->date('valid_until');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('quote_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Dénormalisé : le devis reste lisible même si le produit change.
            $table->string('product_name');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
