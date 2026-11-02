<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Members registered through the web form never got a savings account, so
 * their deposits could not be recorded. Open an empty one for each of them.
 * Only inserts rows; existing accounts and balances are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $next = (int) DB::table('savings_accounts')->where('account_number', 'like', 'SAV-%')->pluck('account_number')
            ->map(fn (string $n) => (int) substr($n, 4))->max() + 1;

        $members = DB::table('members')
            ->whereNull('deleted_at')
            ->whereNotIn('id', DB::table('savings_accounts')->select('member_id'))
            ->orderBy('member_number')
            ->get(['id', 'organization_id', 'registration_date']);

        foreach ($members as $member) {
            DB::table('savings_accounts')->insert([
                'id' => (string) Str::uuid(),
                'organization_id' => $member->organization_id,
                'member_id' => $member->id,
                'account_number' => 'SAV-'.str_pad((string) $next++, 6, '0', STR_PAD_LEFT),
                'balance' => 0,
                'status' => 'active',
                'opened_at' => $member->registration_date ?? now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Only accounts that are still empty and unused are removed.
     */
    public function down(): void
    {
        DB::table('savings_accounts')
            ->where('balance', 0)
            ->whereNotIn('id', DB::table('savings_transactions')->select('savings_account_id'))
            ->whereDate('created_at', '>=', '2026-11-02')
            ->delete();
    }
};
