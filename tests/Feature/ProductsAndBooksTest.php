<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\LoanProduct;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductsAndBooksTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        $staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['products.view', 'products.manage', 'accounting.view', 'accounting.post', 'guarantors.view', 'payments.view', 'profit.view', 'reports.view'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_product_form_shows_the_insurance_fee_and_rate_period(): void
    {
        $this->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('name="insurance_fee"', false)
            ->assertDontSee('type="hidden" name="insurance_fee"', false)
            ->assertSee(t('products.interestRatePerYear'));
    }

    public function test_maximum_amount_cannot_be_below_minimum(): void
    {
        $this->post(route('admin.products.store'), $this->product(['minimum_amount' => 500_000, 'maximum_amount' => 100_000]))
            ->assertSessionHasErrors('maximum_amount');

        $this->post(route('admin.products.store'), $this->product())->assertSessionHasNoErrors();
        $this->assertSame(0.0, (float) LoanProduct::firstOrFail()->insurance_fee);
    }

    public function test_account_codes_are_unique_within_the_group(): void
    {
        Account::create(['organization_id' => $this->organization->id, 'account_code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'balance' => 0]);

        $this->post(route('admin.accounting.accounts.store'), ['account_code' => '1000', 'name' => 'Petty cash', 'account_type' => 'asset'])
            ->assertSessionHasErrors('account_code');
    }

    public function test_finance_pages_render(): void
    {
        foreach (['admin.guarantors.index', 'admin.payments.index', 'admin.profit.index', 'admin.reports.index', 'admin.accounting.index', 'admin.products.index'] as $route) {
            $this->get(route($route, ['q' => 'x']))->assertOk();
        }
        $this->get(route('admin.reports.index'))->assertSee(t('reports.contents.loans'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function product(array $overrides = []): array
    {
        return [
            'name' => 'Biashara', 'minimum_amount' => 50_000, 'maximum_amount' => 2_000_000, 'interest_rate' => 12,
            'interest_method' => 'reducing', 'repayment_period' => 6, 'repayment_frequency' => 'monthly',
            'processing_fee' => 1, 'insurance_fee' => 0, 'status' => 'active', ...$overrides,
        ];
    }
}
