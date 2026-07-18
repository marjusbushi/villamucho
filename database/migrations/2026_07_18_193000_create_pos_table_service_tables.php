<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('number', 20);
            $table->string('name', 80);
            $table->string('area', 80)->default('Salla kryesore');
            $table->unsignedSmallInteger('seats')->default(4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'area', 'number']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });

        Schema::create('pos_order_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->string('destination', 40)->default('banak');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pos_order_id', 'sequence']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->foreignId('pos_order_round_id')->nullable()->after('pos_order_id')
                ->constrained('pos_order_rounds')->nullOnDelete();
            $table->index(['tenant_id', 'pos_order_round_id']);
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->foreignId('pos_table_id')->nullable()->after('table_number')
                ->constrained('pos_tables')->nullOnDelete();
            $table->string('service_status', 24)->default('open')->after('status');
            $table->index(['tenant_id', 'table_number', 'status'], 'pos_orders_table_status_index');
            $table->index(['tenant_id', 'pos_table_id', 'status'], 'pos_orders_pos_table_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('pos_orders_pos_table_status_index');
            $table->dropIndex('pos_orders_table_status_index');
            $table->dropConstrainedForeignId('pos_table_id');
            $table->dropColumn('service_status');
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'pos_order_round_id']);
            $table->dropConstrainedForeignId('pos_order_round_id');
        });

        Schema::dropIfExists('pos_order_rounds');
        Schema::dropIfExists('pos_tables');
    }
};
