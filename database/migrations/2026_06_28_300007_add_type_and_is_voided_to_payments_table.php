<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('type')->default('payment')->nullable()->comment('payment|refund|deposit|writeoff — amount stays positive; type distinguishes the entry');
            $table->boolean('is_voided')->default(false)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('payments', 'is_voided')) {
                $table->dropColumn('is_voided');
            }
        });
    }
};
