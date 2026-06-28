<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->unsignedInteger('total_rooms');
            $table->unsignedInteger('out_of_order')->default(0);
            $table->unsignedInteger('available')->nullable();
            $table->timestamps();

            $table->unique('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_inventory_snapshots');
    }
};
