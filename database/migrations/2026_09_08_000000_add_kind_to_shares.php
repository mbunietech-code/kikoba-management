<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            // 'regular' = ongoing share purchases · 'opening' = entrance / initial shares
            $table->string('kind')->default('regular')->after('member_id');
            $table->index(['organization_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
