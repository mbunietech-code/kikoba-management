<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\ProfitDistribution;
use App\Models\Project;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Models\Transaction;
use App\Services\AccountingService;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeDemoDataTest extends TestCase
{
    use RefreshDatabase;

    private Member $realMember;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->seed([RolePermissionSeeder::class, DemoSeeder::class]);
        $org = Organization::firstOrFail();

        // a real member added later, with real shares, savings and a real posting
        $this->realMember = Member::factory()->create(['organization_id' => $org->id, 'member_number' => 'MBR-000004']);
        Share::create(['organization_id' => $org->id, 'member_id' => $this->realMember->id, 'kind' => 'regular', 'quantity' => 10, 'price_per_share' => 10_000, 'total_value' => 100_000, 'purchased_at' => now()->toDateString(), 'transaction_reference' => 'SHR-REAL', 'status' => 'confirmed']);
        SavingsAccount::openFor($this->realMember);
        LoanProduct::create(['organization_id' => $org->id, 'name' => 'Emergency', 'minimum_amount' => 10_000, 'maximum_amount' => 500_000, 'interest_rate' => 12, 'repayment_period' => 3]);
        app(AccountingService::class)->post($org->id, 'REAL', 'Real deposit', [['1000', 70_000, 0], ['1100', 0, 70_000]]);
    }

    public function test_it_refuses_while_demo_members_are_not_archived(): void
    {
        $this->artisan('kikoba:purge-demo', ['--force' => true])->assertFailed();

        $this->assertSame(4, Member::count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->archiveDemoMembers();
        $loans = Loan::count();

        $this->artisan('kikoba:purge-demo')->assertSuccessful()->expectsOutputToContain('Dry run');

        $this->assertSame($loans, Loan::count());
        $this->assertSame(4, Member::withTrashed()->count());
    }

    public function test_force_removes_demo_records_and_keeps_real_ones(): void
    {
        $this->archiveDemoMembers();

        $this->artisan('kikoba:purge-demo', ['--force' => true])->assertSuccessful();

        $this->assertSame(['MBR-000004'], Member::withTrashed()->pluck('member_number')->all());
        $this->assertSame(0, Loan::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, ProfitDistribution::count());
        $this->assertSame(0, Project::withTrashed()->count());
        $this->assertSame(['Emergency'], LoanProduct::withTrashed()->pluck('name')->all());
        $this->assertSame(['SHR-REAL'], Share::pluck('transaction_reference')->all());
        $this->assertSame(1, SavingsAccount::count());
        $this->assertFalse(JournalEntry::where('reference', 'JE-2026-00001')->exists());

        // balances now equal the real posting only (seeded opening figures are gone)
        $balances = Account::pluck('balance', 'account_code');
        $this->assertSame(70_000, (int) $balances['1000']);
        $this->assertSame(70_000, (int) $balances['1100']);
        $this->assertSame(0, (int) $balances['1010']);
        $this->assertSame(0, (int) $balances['1400']);
    }

    private function archiveDemoMembers(): void
    {
        Member::whereIn('member_number', ['MBR-000001', 'MBR-000002', 'MBR-000003'])->get()->each->delete();
    }
}
