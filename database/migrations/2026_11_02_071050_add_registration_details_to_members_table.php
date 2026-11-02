<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('nida_number', 20)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('alternative_phone', 20)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('street', 150)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('employer', 150)->nullable();
            $table->string('next_of_kin_relationship', 100)->nullable();
            $table->string('next_of_kin_address')->nullable();
            $table->string('photo_path')->nullable();
            $table->unique(['organization_id', 'nida_number'], 'members_org_nida_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_org_nida_unique');
            $table->dropColumn([
                'nida_number', 'nationality', 'marital_status', 'alternative_phone',
                'region', 'district', 'ward', 'street', 'occupation', 'employer',
                'next_of_kin_relationship', 'next_of_kin_address', 'photo_path',
            ]);
        });
    }
};
