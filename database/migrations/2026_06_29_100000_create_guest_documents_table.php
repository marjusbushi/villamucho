<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('other'); // passport, id_card, drivers_license, visa, other
            $table->string('original_name');
            $table->string('path');                    // path on the PRIVATE 'local' disk
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_documents');
    }
};
