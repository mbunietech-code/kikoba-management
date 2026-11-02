<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProfitDistribution;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\ProfitDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProfitDistributionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    /** @var array<int, Member> */
    private array $members;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        foreach ([['1000', 'Cash', 'asset'], ['1100', 'Member Savings', 'liability'], ['3000', 'Retained Earnings', 'equity'], ['4000', 'Interest Income', 'revenue'], ['5100', 'Operating Expenses', 'expense']] as [$code, $name, $type]) {
            Account::create(['organization_id' => $this->organization->id, 'account_code' => $code, 'name' => $name, 'account_type' => $type, 'balance' => 0]);
        }
        // three members holding 1, 1 and 1 share-units → a three-way split with a remainder
        $this->members = Member::factory()->count(3)->create(['organization_id' => $this->organization->id, 'registration_date' => '2026-01-01'])->all();
        foreach ($this->members as $i => $member) {
            Share::create(['organization_id' => $this->organization->id, 'member_id' => $member->id, 'kind' => 'regular', 'quantity' => 1, 'price_per_share' => 10_000, 'total_value' => 10_000, 'purchased_at' => '2026-02-01', 'transaction_reference' => 'SHR-'.$i, 'status' => 'confirmed']);
        }

        $staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['profit.view', 'profit.manage'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_profit_from_the_books_is_income_minus_expenses(): void
    {
        $books = app(AccountingService::class);
        $books->post($this->organization->id, 'A', 'Interest', [['1000', 150_000, 0], ['4000', 0, 150_000]]);
        $books->post($this->organization->id, 'B', 'Rent', [['5100', 40_000, 0], ['1000', 0, 40_000]]);

        $this->get(route('admin.profit.create', ['period_start' => now()->startOfYear()->toDateString(), 'period_end' => now()->toDateString()]))
            ->assertOk()->assertSee(money(110_000));
    }

    public function test_shares_add_up_exactly_and_the_group_keeps_its_reserve(): void
    {
        $this->post(route('admin.profit.store'), $this->form(['total_profit' => 100_000, 'reserve_percent' => 10]))->assertSessionHasNoErrors();

        $distribution = ProfitDistribution::with('allocations')->firstOrFail();
        $this->assertSame(10_000, $distribution->reserved_amount);
        $this->assertSame(90_000, $distribution->distributable_profit);
        $this->assertSame(90_000, (int) $distribution->allocations->sum('amount'));
        $this->assertEqualsCanonicalizing([30_000, 30_000, 30_000], $distribution->allocations->pluck('amount')->map(fn ($a) => (int) $a)->all());
    }

    public function test_largest_remainder_keeps_the_total_exact(): void
    {
        $split = app(ProfitDistributionService::class)->split(100, collect(['a' => 1, 'b' => 1, 'c' => 1]));

        $this->assertSame(100, $split->sum('amount'));
        $this->assertEqualsCanonicalizing([34, 33, 33], $split->pluck('amount')->all());
    }

    public function test_paying_into_savings_credits_each_member_and_posts_the_books(): void
    {
        $this->post(route('admin.profit.store'), $this->form(['total_profit' => 90_000]));
        $distribution = ProfitDistribution::firstOrFail();

        $this->post(route('admin.profit.distribute', $distribution), ['method' => 'savings'])->assertSessionHas('toast_tone', 'error');
        $this->post(route('admin.profit.approve', $distribution));
        $this->post(route('admin.profit.distribute', $distribution), ['method' => 'savings'])->assertSessionHasNoErrors();

        $this->assertSame('distributed', $distribution->fresh()->status);
        foreach ($this->members as $member) {
            $this->assertSame(30_000, SavingsAccount::where('member_id', $member->id)->value('balance'));
        }
        $this->assertSame(-90_000, (int) Account::where('account_code', '3000')->value('balance'));
        $this->assertSame(90_000, (int) Account::where('account_code', '1100')->value('balance'));

        // a second pay-out of the same distribution is refused
        $this->post(route('admin.profit.distribute', $distribution), ['method' => 'savings']);
        $this->assertSame(30_000, SavingsAccount::where('member_id', $this->members[0]->id)->value('balance'));
    }

    public function test_cash_pay_out_reduces_cash_and_leaves_savings_alone(): void
    {
        $this->post(route('admin.profit.store'), $this->form(['total_profit' => 60_000, 'basis' => 'equal']));
        $distribution = ProfitDistribution::firstOrFail();
        $this->post(route('admin.profit.approve', $distribution));
        $this->post(route('admin.profit.distribute', $distribution), ['method' => 'cash']);

        $this->assertSame(-60_000, (int) Account::where('account_code', '1000')->value('balance'));
        $this->assertSame(0, (int) Account::where('account_code', '1100')->value('balance'));
        $this->assertSame(3, $distribution->allocations()->where('status', 'paid')->count());
    }

    public function test_an_overlapping_period_cannot_be_shared_twice(): void
    {
        $this->post(route('admin.profit.store'), $this->form());
        $this->post(route('admin.profit.approve', ProfitDistribution::firstOrFail()));

        $this->post(route('admin.profit.store'), $this->form())->assertSessionHasErrors('period_start');
        $this->assertSame(1, ProfitDistribution::count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return ['period_start' => '2026-01-01', 'period_end' => now()->toDateString(), 'total_profit' => 90_000, 'reserve_percent' => 0, 'basis' => 'shares', ...$overrides];
    }
}
