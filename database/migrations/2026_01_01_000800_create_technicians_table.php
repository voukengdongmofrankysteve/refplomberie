<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('specialty');
            $table->string('experience');
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('jobs_count')->default(0);
            $table->string('photo');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
