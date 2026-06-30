<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A price set for ONE specific date + room type (e.g. accepted from a Smart Pricing
        // suggestion). Overrides the seasonal/base price for that night only. One per pair.
        Schema::create('rate_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_overrides');
    }
};
