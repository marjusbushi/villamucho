<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only audit of every channel-manager sync (push availability/rates,
     * pull, or inbound webhook). Without it, a failed sync is invisible — this is
     * what lets us see and retry problems.
     */
    public function up(): void
    {
        Schema::create('channel_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->default('beds24');
            $table->string('direction'); // push | pull | webhook
            $table->string('action')->nullable();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('status')->nullable(); // ok | error
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['channel', 'direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_sync_logs');
    }
};
