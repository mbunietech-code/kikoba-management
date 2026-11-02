<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoanSafeguardsTest extends TestCase
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
        $this->member = Member::factory()->create(['organization_id' => $this->organization->id]);
        $this->product = LoanProduct::create([
            'organization_id' => $this->organization->id, 'name' => 'Biashara', 'minimum_amount' => 10_000,
            'maximum_amount' => 5_000_000, 'interest_rate' => 2, 'repayment_period' => 3,
        ]);

        $staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['loans.view', 'loans.approve', 'loans.reject', 'loans.repay', 'loans.disburse'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_a_running_loan_cannot_be_approved_again(): void
    {
        $loan = $this->loan(['status' => 'active']);

        $this->post(route('admin.loans.approve', $loan))->assertRedirect();

        $this->assertSame('active', $loan->fresh()->status);
    }

    public function test_a_submitted_loan_can_be_approved(): void
    {
        $loan = $this->loan(['status' => 'submitted']);

        $this->post(route('admin.loans.approve', $loan))->assertRedirect();

        $this->assertSame('approved', $loan->fresh()->status);
    }

    public function test_repayment_cannot_exceed_the_outstanding_balance(): void
    {
        $loan = $this->loan(['status' => 'active', 'total_amount' => 100_000, 'outstanding_balance' => 100_000]);

        $this->post(route('admin.loans.repay', $loan), ['amount' => 150_000])->assertSessionHasErrors('amount');

        $this->assertSame(100_000, $loan->fresh()->outstanding_balance);
    }

    public function test_repayment_is_refused_for_a_loan_that_is_not_running(): void
    {
        $loan = $this->loan(['status' => 'approved', 'total_amount' => 100_000, 'outstanding_balance' => 100_000]);

        $this->post(route('admin.loans.repay', $loan), ['amount' => 10_000])->assertRedirect();

        $this->assertSame(0, $loan->fresh()->amount_paid);
    }

    public function test_late_instalments_mark_the_loan_overdue_and_catching_up_restores_it(): void
    {
        $loan = $this->loan(['status' => 'active', 'total_amount' => 60_000, 'outstanding_balance' => 60_000]);
        $late = $this->instalment($loan, 1, now()->subDays(5)->toDateString());
        $future = $this->instalment($loan, 2, now()->addMonth()->toDateString());

        app(LoanService::class)->markOverdue($this->organization->id);

        $this->assertSame('overdue', $late->fresh()->status);
        $this->assertSame('pending', $future->fresh()->status);
        $this->assertSame('overdue', $loan->fresh()->status);

        $this->post(route('admin.loans.repay', $loan), ['amount' => 30_000])->assertSessionHasNoErrors();

        $this->assertSame('paid', $late->fresh()->status);
        $this->assertSame('active', $loan->fresh()->status);
        $this->assertSame(30_000, $loan->fresh()->outstanding_balance);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function loan(array $attributes): Loan
    {
        return Loan::create([
            'organization_id' => $this->organization->id,
            'member_id' => $this->member->id,
            'loan_product_id' => $this->product->id,
            'loan_number' => 'LN-'.fake()->unique()->numerify('######'),
            'principal_amount' => 100_000,
            'period' => 3,
            'application_date' => now()->toDateString(),
            ...$attributes,
        ]);
    }

    private function instalment(Loan $loan, int $number, string $dueDate): LoanRepaymentSchedule
    {
        return LoanRepaymentSchedule::create([
            'loan_id' => $loan->id, 'installment_number' => $number, 'due_date' => $dueDate,
            'principal_due' => 25_000, 'interest_due' => 5_000, 'fee_due' => 0, 'total_due' => 30_000,
            'status' => 'pending',
        ]);
    }
}
