<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('custom'); // monthly three_months long_term custom
            $table->bigInteger('capital_required')->default(0);
            $table->bigInteger('capital_raised')->default(0);
            $table->bigInteger('expected_profit')->default(0);
            $table->bigInteger('actual_profit')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('planned'); // planned active completed cancelled
            $table->string('manager')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_investments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('profit_share')->default(0);
            $table->string('status')->default('active'); // active completed
            $table->date('invested_at');
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name');
            $table->bigInteger('monthly_contribution');
            $table->bigInteger('coverage_amount');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active expired suspended cancelled
            $table->timestamps();
        });

        Schema::create('insurance_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('insurance_account_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('period'); // YYYY-MM
            $table->foreignUuid('transaction_id')->nullable();
            $table->foreignUuid('payment_id')->nullable();
            $table->date('paid_on');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('insurance_account_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('claim_number')->unique();
            $table->string('claim_type');
            $table->text('description')->nullable();
            $table->bigInteger('amount_requested');
            $table->bigInteger('amount_approved')->default(0);
            $table->string('status')->default('submitted'); // submitted under_review approved rejected paid cancelled
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_contributions');
        Schema::dropIfExists('insurance_accounts');
        Schema::dropIfExists('project_investments');
        Schema::dropIfExists('projects');
    }
};
