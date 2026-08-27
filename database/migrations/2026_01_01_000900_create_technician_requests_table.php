<?php

use App\Enums\TechnicianRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Assigné par l'admin depuis le back-office.
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('address');
            $table->string('service');
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time')->nullable();
            $table->text('description');
            $table->string('status')->default(TechnicianRequestStatus::Pending->value);
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_requests');
    }
};
