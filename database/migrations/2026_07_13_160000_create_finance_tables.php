<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance module (Phase 1 schema, MHQ module #11).
     *
     * finance_accounts  — where the money sits (Arka cash / bank accounts).
     * finance_payments  — ONE unified ledger: in / out / transfer. A payment
     *                     debits an account and credits a document (or vice
     *                     versa). Documents link via nullable bill_id /
     *                     invoice_id FKs (a bill takes MANY partial payments);
     *                     the sourceable morph is reserved for the AUTO-feed
     *                     origin (folio payment, POS shift) and is UNIQUE so
     *                     re-running an import can never double-count.
     * suppliers/bills   — payables (multi-currency, fx frozen per document).
     * invoices          — receivables (auto from folio checkout + manual B2B).
     *
     * Money decimal(12,2); fx_rate decimal(10,4) frozen at document time;
     * amount_base is ALWAYS the EUR value (equal to amount when currency=EUR).
     * All tables tenant-scoped. No fiscalization fields beyond nipt + serial.
     */
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'bank']);
            $table->string('currency', 3)->default('EUR');
            $table->string('iban')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('nipt', 20)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            // Block deleting a supplier that still has bills (history integrity).
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('number', 60)->nullable();
            $table->string('category', 60)->default('Të tjera');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('fx_rate', 10, 4)->nullable();
            $table->decimal('total', 12, 2);
            $table->decimal('total_base', 12, 2);
            $table->enum('status', ['open', 'partial', 'paid'])->default('open');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'due_date']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            // Serial per year: "2026-000123". Uniqueness enforced per tenant.
            $table->string('number', 30);
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('company_nipt', 20)->nullable();
            $table->string('company_address')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('total', 12, 2);
            $table->enum('status', ['open', 'partial', 'paid'])->default('open');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status', 'due_date']);
        });

        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out', 'transfer']);
            // Accounts are history — never deletable while movements reference them.
            $table->foreignId('account_id')->constrained('finance_accounts')->restrictOnDelete();
            // Transfers are ONE row: money leaves account_id, enters counter_account_id.
            $table->foreignId('counter_account_id')->nullable()->constrained('finance_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('fx_rate', 10, 4)->nullable();
            $table->decimal('amount_base', 12, 2);
            $table->enum('method', ['cash', 'card', 'bank', 'pok', 'ota'])->default('cash');
            $table->enum('source', ['auto', 'manual'])->default('manual');
            // Document being settled (many partial payments per document).
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            // AUTO-feed origin (folio payment / POS shift): unique => idempotent.
            $table->nullableMorphs('sourceable');
            $table->string('description', 300)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'sourceable_type', 'sourceable_id'], 'finance_payments_source_unique');
            $table->index(['tenant_id', 'account_id', 'paid_at']);
            $table->index(['tenant_id', 'direction', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('finance_accounts');
    }
};
