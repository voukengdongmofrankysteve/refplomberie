<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            // Canaux retenus pour la diffusion. La notification en base part
            // toujours : elle n'apparaît donc pas dans ce choix.
            $table->json('channels')->nullable()->after('product_ids');
            $table->unsignedInteger('pushed_count')->default(0)->after('recipients_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['channels', 'pushed_count']);
        });
    }
};
