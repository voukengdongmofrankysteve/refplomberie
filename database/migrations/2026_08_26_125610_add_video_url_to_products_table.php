<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Facultatif : la grande majorité des fiches n'en auront jamais,
            // et rien ne doit s'afficher côté vitrine tant qu'elle est vide.
            $table->string('video_url')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('video_url');
        });
    }
};
