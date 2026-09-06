<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('payment_method'); // mobile_money bank card cash manual
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('TZS');
            $table->string('external_reference')->nullable();
            $table->string('internal_reference')->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending'); // pending successful failed reversed
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->index('external_reference');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable();
            $table->string('transaction_reference')->unique();
            $table->string('type'); // SHARE_PURCHASE SAVINGS_DEPOSIT ... REVERSAL
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('TZS');
            $table->string('status')->default('successful'); // pending successful failed reversed
            $table->string('description')->nullable();
            $table->foreignUuid('reversal_of')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'created_at']);
            $table->index('type');
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('account_code');
            $table->string('name');
            $table->string('account_type'); // asset liability equity revenue expense
            $table->foreignUuid('parent_id')->nullable();
            $table->bigInteger('balance')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'account_code']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable();
            $table->string('reference')->unique();
            $table->string('description')->nullable();
            $table->date('entry_date');
            $table->string('posted_by')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained();
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->timestamps();
        });

        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('total_profit');
            $table->bigInteger('reserved_amount')->default(0);
            $table->bigInteger('distributable_profit');
            $table->string('basis')->default('shares'); // shares savings equal
            $table->string('status')->default('draft'); // draft calculated approved distributed
            $table->date('distribution_date')->nullable();
            $table->timestamps();
        });

        Schema::create('profit_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('profit_distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('basis')->default('shares');
            $table->decimal('percentage', 8, 4)->default(0);
            $table->bigInteger('amount');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_allocations');
        Schema::dropIfExists('profit_distributions');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
    }
};
