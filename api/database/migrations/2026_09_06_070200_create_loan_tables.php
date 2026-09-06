<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->bigInteger('minimum_amount');
            $table->bigInteger('maximum_amount');
            $table->decimal('interest_rate', 6, 3);
            $table->string('interest_method')->default('reducing'); // flat reducing
            $table->unsignedSmallInteger('repayment_period');       // months
            $table->string('repayment_frequency')->default('monthly');
            $table->decimal('processing_fee', 6, 3)->default(0);
            $table->decimal('insurance_fee', 6, 3)->default(0);
            $table->decimal('penalty_rate', 6, 3)->default(0);
            $table->bigInteger('minimum_savings')->default(0);
            $table->unsignedInteger('minimum_shares')->default(0);
            $table->unsignedTinyInteger('required_guarantors')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loan_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('loan_product_id')->constrained();
            $table->bigInteger('requested_amount');
            $table->string('purpose')->nullable();
            $table->unsignedSmallInteger('requested_period');
            $table->string('repayment_frequency')->default('monthly');
            $table->string('status')->default('submitted'); // draft submitted under_review approved rejected
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('loan_product_id')->constrained();
            $table->foreignUuid('loan_application_id')->nullable();
            $table->string('loan_number')->unique();
            $table->bigInteger('principal_amount');
            $table->bigInteger('interest_amount')->default(0);
            $table->bigInteger('processing_fee')->default(0);
            $table->bigInteger('insurance_amount')->default(0);
            $table->bigInteger('penalty_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('amount_paid')->default(0);
            $table->bigInteger('outstanding_balance')->default(0);
            $table->string('status')->default('approved'); // approved disbursed active overdue completed defaulted cancelled
            $table->string('purpose')->nullable();
            $table->unsignedSmallInteger('period');
            $table->string('repayment_frequency')->default('monthly');
            $table->string('interest_method')->default('reducing');
            $table->date('application_date');
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'status']);
            $table->index('status');
        });

        Schema::create('guarantors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();          // borrower
            $table->foreignUuid('guarantor_member_id')->constrained('members')->cascadeOnDelete(); // guarantor
            $table->bigInteger('guaranteed_amount');
            $table->string('status')->default('pending'); // pending approved rejected released
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->bigInteger('principal_due')->default(0);
            $table->bigInteger('interest_due')->default(0);
            $table->bigInteger('fee_due')->default(0);
            $table->bigInteger('penalty_due')->default(0);
            $table->bigInteger('total_due')->default(0);
            $table->bigInteger('amount_paid')->default(0);
            $table->string('status')->default('pending'); // pending partial paid overdue
            $table->date('paid_at')->nullable();
            $table->timestamps();
            $table->index('loan_id');
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('schedule_id')->nullable();
            $table->foreignUuid('transaction_id')->nullable();
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->bigInteger('principal_paid')->default(0);
            $table->bigInteger('interest_paid')->default(0);
            $table->bigInteger('fee_paid')->default(0);
            $table->bigInteger('penalty_paid')->default(0);
            $table->bigInteger('total_paid');
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->string('method')->default('cash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loan_repayment_schedules');
        Schema::dropIfExists('guarantors');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_applications');
        Schema::dropIfExists('loan_products');
    }
};
