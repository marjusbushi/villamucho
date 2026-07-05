<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demand signal from the public booking form: every availability search a
 * visitor runs, including the ones that found NOTHING (denials) — the purest
 * "we could have sold this date" evidence for pricing. Zero PII by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_search_logs', function (Blueprint $table) {
            $table->id();
            $table->date('check_in');
            $table->date('check_out');
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('results_count');
            $table->boolean('denied')->default(false);
            $table->string('source', 20)->default('book');
            $table->timestamp('created_at')->nullable();

            $table->index('check_in');
            $table->index(['denied', 'check_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_search_logs');
    }
};
