<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffAreaAccessTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
    }

    public function test_members_are_sent_to_their_portal_instead_of_the_staff_dashboard(): void
    {
        $this->actingAs($this->memberUser());

        $this->get(route('admin.dashboard'))->assertRedirect(route('member.home'));
        $this->get(route('admin.notifications.index'))->assertRedirect(route('member.home'));
    }

    public function test_staff_can_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['organization_id' => $this->organization->id]));

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_member_only_sees_announcements_and_their_own_notifications(): void
    {
        $user = $this->memberUser();
        $other = Member::factory()->create(['organization_id' => $this->organization->id]);
        foreach ([['Group meeting', null], ['Your loan is due', $user->member->id], ['Someone else loan', $other->id]] as [$title, $memberId]) {
            Notification::create(['organization_id' => $this->organization->id, 'member_id' => $memberId, 'title' => $title, 'message' => $title, 'type' => 'announcement']);
        }

        $this->actingAs($user)->get(route('member.notifications'))
            ->assertOk()
            ->assertSee('Group meeting')
            ->assertSee('Your loan is due')
            ->assertDontSee('Someone else loan');
    }

    public function test_cash_flow_comes_from_the_posted_ledger(): void
    {
        foreach ([['1000', 'Cash', 'asset'], ['1100', 'Member Savings', 'liability'], ['1300', 'Loan Receivable', 'asset'], ['1010', 'Bank', 'asset']] as [$code, $name, $type]) {
            Account::create(['organization_id' => $this->organization->id, 'account_code' => $code, 'name' => $name, 'account_type' => $type, 'balance' => 0]);
        }
        $books = app(AccountingService::class);
        $books->post($this->organization->id, 'T1', 'Deposit', [['1000', 300_000, 0], ['1100', 0, 300_000]]);
        $books->post($this->organization->id, 'T2', 'Disbursement', [['1300', 120_000, 0], ['1010', 0, 120_000]]);

        $flow = app(DashboardMetrics::class)->cashFlow($this->organization->id);

        $this->assertCount(6, $flow['labels']);
        $this->assertSame(300_000, end($flow['inflow']));
        $this->assertSame(120_000, end($flow['outflow']));
        $this->assertSame(0, array_sum(array_slice($flow['inflow'], 0, 5)));
    }

    private function memberUser(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole(Role::findOrCreate('member', 'web'));
        Member::factory()->create(['organization_id' => $this->organization->id, 'user_id' => $user->id]);

        return $user->fresh();
    }
}
