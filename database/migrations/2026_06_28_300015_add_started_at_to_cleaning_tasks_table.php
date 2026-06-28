<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaning_tasks', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->comment('When cleaning began, for turnaround minutes');
        });
    }

    public function down(): void
    {
        Schema::table('cleaning_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('cleaning_tasks', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }
};
