<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cleaning_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['checkout_clean', 'stayover_clean', 'deep_clean', 'inspection'])->default('checkout_clean');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'inspected'])->default('pending');
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->text('notes')->nullable();
            $table->text('issue_reported')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
            $table->index(['room_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_tasks');
    }
};
