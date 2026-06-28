<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('period')->comment('Budget period in YYYY-MM format');
            $table->decimal('revenue_target', 10, 2)->nullable();
            $table->decimal('adr_target', 10, 2)->nullable();
            $table->decimal('occupancy_target', 5, 2)->nullable();
            $table->decimal('revpar_target', 10, 2)->nullable();
            $table->timestamps();

            $table->unique('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
