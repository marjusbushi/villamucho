<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_documents', function (Blueprint $table) {
            $table->string('ai_status')->default('pending')->after('uploaded_by');
            $table->json('ai_extraction')->nullable()->after('ai_status');
            $table->string('ai_model')->nullable()->after('ai_extraction');
            $table->text('ai_error')->nullable()->after('ai_model');
            $table->timestamp('ai_extracted_at')->nullable()->after('ai_error');
            $table->timestamp('ai_reviewed_at')->nullable()->after('ai_extracted_at');
            $table->foreignId('ai_reviewed_by')->nullable()->after('ai_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_reviewed_by');
            $table->dropColumn(['ai_status', 'ai_extraction', 'ai_model', 'ai_error', 'ai_extracted_at', 'ai_reviewed_at']);
        });
    }
};
