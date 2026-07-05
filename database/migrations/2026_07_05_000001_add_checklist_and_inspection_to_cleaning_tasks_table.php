<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the per-task cleaning checklist snapshot + the inspection audit trail.
     * started_at already exists (migration 2026_06_28_300015) — do NOT re-add it here.
     */
    public function up(): void
    {
        Schema::table('cleaning_tasks', function (Blueprint $table) {
            // Per-task SNAPSHOT of the checklist: [{ label, done, done_at }].
            // Copied from the settings template at start so later template edits
            // never corrupt an in-flight task. NULL = no checklist / legacy row.
            if (! Schema::hasColumn('cleaning_tasks', 'checklist')) {
                $table->json('checklist')->nullable()->after('issue_reported');
            }

            // Who pressed Fillo (audit; started_at is populated in the backend task).
            if (! Schema::hasColumn('cleaning_tasks', 'started_by')) {
                $table->foreignId('started_by')->nullable()->after('assigned_to')
                    ->constrained('users')->nullOnDelete();
            }

            // Inspection audit trail.
            if (! Schema::hasColumn('cleaning_tasks', 'inspected_by')) {
                $table->foreignId('inspected_by')->nullable()->after('started_by')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('cleaning_tasks', 'inspected_at')) {
                $table->timestamp('inspected_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cleaning_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('cleaning_tasks', 'inspected_by')) {
                $table->dropConstrainedForeignId('inspected_by');
            }
            if (Schema::hasColumn('cleaning_tasks', 'started_by')) {
                $table->dropConstrainedForeignId('started_by');
            }
            if (Schema::hasColumn('cleaning_tasks', 'inspected_at')) {
                $table->dropColumn('inspected_at');
            }
            if (Schema::hasColumn('cleaning_tasks', 'checklist')) {
                $table->dropColumn('checklist');
            }
        });
    }
};
