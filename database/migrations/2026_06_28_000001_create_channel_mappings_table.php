<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps each of our room_types to its Beds24 room id, so a PMS room type can be
     * pushed to / received from the channel manager. One row per (channel, room_type).
     */
    public function up(): void
    {
        Schema::create('channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->default('beds24');
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('beds24_prop_id');
            $table->string('beds24_room_id');
            $table->timestamps();

            $table->unique(['channel', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_mappings');
    }
};
