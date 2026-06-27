<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make document_number unique (NULLs stay distinct, so guests without an ID doc are unaffected).
     * Replaces the plain index with a unique one to prevent duplicate guest records.
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['document_number']);
            $table->unique('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->index('document_number');
        });
    }
};
