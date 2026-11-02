<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SavingsAndReversalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private SavingsAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        foreach ([['1000', 'Cash', 'asset'], ['1100', 'Member Savings', 'liability']] as [$code, $name, $type]) {
            Account::create(['organization_id' => $this->organization->id, 'account_code' => $code, 'name' => $name, 'account_type' => $type, 'balance' => 0]);
        }
        $member = Member::factory()->create(['organization_id' => $this->organization->id]);
        $this->account = SavingsAccount::create(['organization_id' => $this->organization->id, 'member_id' => $member->id, 'account_number' => 'SAV-0001', 'balance' => 0, 'status' => 'active', 'opened_at' => now()]);

        $staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['savings.view', 'savings.deposit', 'savings.withdraw', 'reports.view', 'accounting.post', 'shares.view', 'shares.create', 'members.view'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_reversing_a_deposit_restores_the_balance_and_the_books(): void
    {
        $this->post(route('admin.savings.deposit'), ['account_id' => $this->account->id, 'amount' => 50_000])->assertSessionHasNoErrors();
        $txn = Transaction::where('type', 'SAVINGS_DEPOSIT')->firstOrFail();
        $this->assertSame(50_000, (int) $this->balanceOf('1000'));

        $this->post(route('admin.transactions.reverse', $txn), ['reason' => 'Wrong member'])->assertRedirect();

        $this->assertSame('reversed', $txn->fresh()->status);
        $this->assertSame(0, $this->account->fresh()->balance);
        $this->assertSame(0, (int) $this->balanceOf('1000'));
        $this->assertSame(0, (int) $this->balanceOf('1100'));

        $this->get(route('admin.savings.index'))->assertOk()->assertSee(money(0));
    }

    public function test_a_transaction_cannot_be_reversed_twice_or_without_a_reason(): void
    {
        $this->post(route('admin.savings.deposit'), ['account_id' => $this->account->id, 'amount' => 20_000]);
        $txn = Transaction::firstOrFail();

        $this->post(route('admin.transactions.reverse', $txn), [])->assertSessionHasErrors('reason');
        $this->post(route('admin.transactions.reverse', $txn), ['reason' => 'Duplicate']);
        $this->post(route('admin.transactions.reverse', $txn), ['reason' => 'Duplicate'])->assertSessionHas('toast_tone', 'error');

        $this->assertSame(1, Transaction::where('type', 'REVERSAL')->count());
        $this->assertSame(0, $this->account->fresh()->balance);
    }

    public function test_loan_transactions_cannot_be_reversed_from_the_generic_screen(): void
    {
        $txn = Transaction::create(['organization_id' => $this->organization->id, 'transaction_reference' => 'TXN-X-1', 'type' => 'LOAN_REPAYMENT', 'amount' => 10_000, 'status' => 'successful']);

        $this->post(route('admin.transactions.reverse', $txn), ['reason' => 'Test'])->assertSessionHas('toast_tone', 'error');

        $this->assertSame('successful', $txn->fresh()->status);
    }

    public function test_reversing_a_deposit_that_was_already_withdrawn_is_refused(): void
    {
        $this->post(route('admin.savings.deposit'), ['account_id' => $this->account->id, 'amount' => 30_000]);
        $this->post(route('admin.savings.withdraw'), ['account_id' => $this->account->id, 'amount' => 30_000]);
        $deposit = Transaction::where('type', 'SAVINGS_DEPOSIT')->firstOrFail();

        $this->post(route('admin.transactions.reverse', $deposit), ['reason' => 'Test'])->assertSessionHas('toast_tone', 'error');

        $this->assertSame(0, $this->account->fresh()->balance);
    }

    public function test_deposit_picker_lists_every_account_not_just_the_current_page(): void
    {
        Member::factory()->count(16)->create(['organization_id' => $this->organization->id])
            ->each(fn (Member $m, int $i) => SavingsAccount::create(['organization_id' => $this->organization->id, 'member_id' => $m->id, 'account_number' => 'SAV-1'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'balance' => 1_000 + $i, 'status' => 'active', 'opened_at' => now()]));

        $html = $this->get(route('admin.savings.index'))->assertOk()->getContent();

        // 17 accounts, offered in both the deposit and the withdrawal dialog
        $this->assertSame(34, preg_match_all('/<option value="[0-9a-f-]{36}"/', $html));
    }

    public function test_share_purchase_uses_its_own_reference_and_rejects_future_dates(): void
    {
        $member = $this->account->member;

        $this->post(route('admin.shares.store'), ['member_id' => $member->id, 'quantity' => 2, 'price_per_share' => 10_000, 'purchased_at' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('purchased_at');
        $this->post(route('admin.shares.store'), ['member_id' => $member->id, 'quantity' => 2, 'price_per_share' => 10_000])->assertSessionHasNoErrors();

        $share = Share::firstOrFail();
        $this->assertSame(20_000, (int) $share->total_value);
        $this->assertStringStartsWith('SHR-'.now()->format('Y').'-', $share->transaction_reference);
    }

    private function balanceOf(string $code): int
    {
        return (int) Account::where('organization_id', $this->organization->id)->where('account_code', $code)->value('balance');
    }
}
