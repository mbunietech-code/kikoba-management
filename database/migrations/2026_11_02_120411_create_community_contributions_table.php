<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jamii (social fund) and Dharura (emergency fund) contributions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('fund', 20);                 // jamii | dharura
            $table->bigInteger('amount');
            $table->string('period', 7);                // YYYY-MM the contribution is for
            $table->date('paid_on');
            $table->string('reference')->nullable();    // receipt / M-Pesa code
            $table->foreignUuid('transaction_id')->nullable();
            $table->string('status', 20)->default('recorded'); // recorded | reversed
            $table->string('reversal_reason')->nullable();
            $table->string('recorded_by')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'period', 'fund']);
            $table->index(['member_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_contributions');
    }
};
