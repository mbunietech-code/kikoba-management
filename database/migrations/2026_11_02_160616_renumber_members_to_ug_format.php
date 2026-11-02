<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Member numbers move from MBR-000004 to the short UG-001 form. Current
 * members are renumbered in their existing order; archived members keep
 * their old number so it stays out of the new sequence.
 */
return new class extends Migration
{
    public function up(): void
    {
        $next = (int) DB::table('members')->where('member_number', 'like', 'UG-%')->pluck('member_number')
            ->map(fn (string $n) => (int) substr($n, 3))->max() + 1;

        $members = DB::table('members')
            ->whereNull('deleted_at')
            ->where('member_number', 'like', 'MBR-%')
            ->orderBy('member_number')
            ->get(['id']);

        foreach ($members as $member) {
            DB::table('members')->where('id', $member->id)->update([
                'member_number' => 'UG-'.str_pad((string) $next++, 3, '0', STR_PAD_LEFT),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * The old numbers are not kept, so this cannot be undone.
     */
    public function down(): void {}
};
