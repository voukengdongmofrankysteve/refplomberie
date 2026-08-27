<?php

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default(PurchaseOrderStatus::Draft->value);
            $table->date('expected_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('total')->default(0);
            // Posée une seule fois, au moment précis où le stock est
            // incrémenté : empêche qu'une même livraison ne soit comptée
            // deux fois si le bon repasse par « reçu ».
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
