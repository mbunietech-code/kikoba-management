<?php

namespace Tests\Feature;

use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InsuranceAndProjectsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        $this->member = Member::factory()->create(['organization_id' => $this->organization->id]);
        $staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['insurance.view', 'insurance.manage', 'projects.view', 'projects.manage'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_a_claim_follows_submitted_approved_paid(): void
    {
        $claim = $this->claim();

        $this->post(route('admin.insurance.claims.decide', $claim), ['decision' => 'pay'])->assertSessionHas('toast_tone', 'error');
        $this->assertSame('submitted', $claim->fresh()->status);

        $this->post(route('admin.insurance.claims.decide', $claim), ['decision' => 'approve', 'amount_approved' => 900_000])->assertSessionHas('toast_tone', 'error');
        $this->post(route('admin.insurance.claims.decide', $claim), ['decision' => 'approve']);
        $this->post(route('admin.insurance.claims.decide', $claim), ['decision' => 'pay']);
        $this->post(route('admin.insurance.claims.decide', $claim), ['decision' => 'reject'])->assertSessionHas('toast_tone', 'error');

        $this->assertSame('paid', $claim->fresh()->status);
        $this->assertSame(200_000, (int) $claim->fresh()->amount_approved);
    }

    public function test_a_project_with_investments_is_cancelled_not_deleted(): void
    {
        $project = Project::create(['organization_id' => $this->organization->id, 'name' => 'Kuku', 'type' => 'long_term', 'capital_required' => 1_000_000, 'status' => 'active']);
        $this->post(route('admin.projects.invest', $project), ['member_id' => $this->member->id, 'amount' => 100_000])->assertSessionHasNoErrors();

        $this->delete(route('admin.projects.destroy', $project));

        $this->assertSame('cancelled', $project->fresh()->status);
        $this->assertSame(1, ProjectInvestment::count());
        $this->post(route('admin.projects.invest', $project), ['member_id' => $this->member->id, 'amount' => 50_000])->assertSessionHas('toast_tone', 'error');
        $this->assertSame(100_000, (int) $project->fresh()->capital_raised);
    }

    public function test_projects_are_in_the_staff_menu(): void
    {
        $this->get(route('admin.projects.index'))->assertOk()->assertSee(route('admin.projects.index'), false);
    }

    private function claim(): InsuranceClaim
    {
        $account = InsuranceAccount::create(['organization_id' => $this->organization->id, 'member_id' => $this->member->id, 'plan_name' => 'Afya', 'monthly_contribution' => 5_000, 'coverage_amount' => 1_000_000, 'status' => 'active', 'start_date' => now()->toDateString()]);

        return InsuranceClaim::create(['organization_id' => $this->organization->id, 'insurance_account_id' => $account->id, 'member_id' => $this->member->id, 'claim_number' => 'CLM-1', 'claim_type' => 'medical', 'amount_requested' => 200_000, 'status' => 'submitted', 'submitted_at' => now()]);
    }
}
