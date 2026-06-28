<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folio_items', function (Blueprint $table) {
            if (! Schema::hasColumn('folio_items', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->nullable()->after('amount')
                    ->comment('VAT rate (%) in force when the charge was created, snapshotted for accurate historic VAT reports');
            }
        });
    }

    public function down(): void
    {
        Schema::table('folio_items', function (Blueprint $table) {
            if (Schema::hasColumn('folio_items', 'vat_rate')) {
                $table->dropColumn('vat_rate');
            }
        });
    }
};
