<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Member $member;

    private LoanProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole(Role::findOrCreate('member', 'web'));
        $this->member = Member::factory()->create(['organization_id' => $this->organization->id, 'user_id' => $user->id]);
        $this->product = LoanProduct::create([
            'organization_id' => $this->organization->id, 'name' => 'Dharura', 'minimum_amount' => 50_000,
            'maximum_amount' => 1_000_000, 'interest_rate' => 12, 'repayment_period' => 6,
        ]);
        $this->actingAs($user);
    }

    public function test_an_application_becomes_a_loan_staff_can_review(): void
    {
        $this->post(route('member.loans.apply.store'), ['loan_product_id' => $this->product->id, 'amount' => 300_000, 'period' => 3, 'purpose' => 'Stock'])
            ->assertRedirect(route('member.loans'));

        $loan = Loan::where('member_id', $this->member->id)->firstOrFail();
        $this->assertSame('submitted', $loan->status);
        $this->assertSame(300_000, (int) $loan->principal_amount);
        $this->assertNotNull($loan->loan_application_id);
    }

    public function test_amount_and_period_must_fit_the_loan_type(): void
    {
        $this->post(route('member.loans.apply.store'), ['loan_product_id' => $this->product->id, 'amount' => 5_000_000, 'period' => 12, 'purpose' => 'Car'])
            ->assertSessionHasErrors(['amount', 'period']);

        $this->assertSame(0, Loan::count());
    }

    public function test_only_one_application_can_wait_at_a_time(): void
    {
        $payload = ['loan_product_id' => $this->product->id, 'amount' => 100_000, 'period' => 2, 'purpose' => 'School fees'];
        $this->post(route('member.loans.apply.store'), $payload);
        $this->post(route('member.loans.apply.store'), $payload)->assertSessionHas('toast_tone', 'error');

        $this->assertSame(1, Loan::count());
    }

    public function test_home_shows_no_invented_profit_and_real_savings_history(): void
    {
        Share::create(['organization_id' => $this->organization->id, 'member_id' => $this->member->id, 'kind' => 'regular', 'quantity' => 100, 'price_per_share' => 10_000, 'total_value' => 1_000_000, 'purchased_at' => now()->toDateString(), 'transaction_reference' => 'SHR-1', 'status' => 'confirmed']);
        $account = SavingsAccount::create(['organization_id' => $this->organization->id, 'member_id' => $this->member->id, 'account_number' => 'SAV-1', 'balance' => 80_000, 'status' => 'active', 'opened_at' => now()]);
        SavingsTransaction::create(['organization_id' => $this->organization->id, 'savings_account_id' => $account->id, 'member_id' => $this->member->id, 'type' => 'deposit', 'amount' => 80_000, 'balance_before' => 0, 'balance_after' => 80_000, 'reference' => 'R1']);

        $response = $this->get(route('member.home'))->assertOk();

        // 1,000,000 of shares used to be shown as 42,000 "profit"
        $response->assertDontSee(money(42_000));
        $response->assertSee('80000');
    }

    public function test_statement_downloads_the_members_own_movements(): void
    {
        $account = SavingsAccount::create(['organization_id' => $this->organization->id, 'member_id' => $this->member->id, 'account_number' => 'SAV-1', 'balance' => 25_000, 'status' => 'active', 'opened_at' => now()]);
        SavingsTransaction::create(['organization_id' => $this->organization->id, 'savings_account_id' => $account->id, 'member_id' => $this->member->id, 'type' => 'deposit', 'amount' => 25_000, 'balance_before' => 0, 'balance_after' => 25_000, 'reference' => 'MPESA-XYZ']);

        $csv = $this->get(route('member.statements.download', ['period' => 'year']))->assertOk()->streamedContent();

        $this->assertStringContainsString('MPESA-XYZ', $csv);
        $this->assertStringContainsString($this->member->member_number, $csv);
    }

    public function test_profile_offers_password_change(): void
    {
        $this->get(route('member.profile'))->assertOk()->assertSee('name="current_password"', false);
        $this->get(route('member.statements'))->assertOk()->assertDontSee('Q2 2026');
    }
}
