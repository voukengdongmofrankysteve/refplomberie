<?php

use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->string('promo_code')->nullable();
            // Produits mis en avant, dans l'ordre choisi par l'administrateur.
            $table->json('product_ids')->nullable();
            $table->string('status')->default(CampaignStatus::Draft->value);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
