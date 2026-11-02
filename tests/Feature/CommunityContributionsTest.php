<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CommunityContribution;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommunityContributionsTest extends TestCase
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
        foreach (['members.view', 'savings.deposit', 'savings.withdraw', 'settings.manage'] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($staff);
    }

    public function test_the_page_shows_the_standard_5000_for_jamii_and_dharura(): void
    {
        $this->get(route('admin.community.index'))
            ->assertOk()
            ->assertSee('name="jamii_amount"', false)
            ->assertSee('name="dharura_amount"', false)
            ->assertSee(money(5_000))
            ->assertSee($this->member->full_name);
    }

    public function test_recording_both_funds_posts_to_the_books(): void
    {
        $this->post(route('admin.community.contributions.store'), $this->payment())->assertSessionHasNoErrors();

        $this->assertSame(2, CommunityContribution::count());
        $this->assertSame(5_000, (int) CommunityContribution::where('fund', 'jamii')->value('amount'));
        $this->assertSame(['COMMUNITY_DHARURA', 'COMMUNITY_JAMII'], Transaction::orderBy('type')->pluck('type')->all());
        $this->assertSame(10_000, $this->balance('1000'));
        $this->assertSame(5_000, $this->balance('2200'));
        $this->assertSame(5_000, $this->balance('2300'));
    }

    public function test_a_member_can_pay_more_than_the_standard_amount(): void
    {
        $this->post(route('admin.community.contributions.store'), $this->payment(['jamii_amount' => 10_000, 'dharura_amount' => 7_500]))->assertSessionHasNoErrors();

        $this->assertSame(10_000, (int) CommunityContribution::where('fund', 'jamii')->value('amount'));
        $this->assertSame(7_500, (int) CommunityContribution::where('fund', 'dharura')->value('amount'));
        $this->assertSame(17_500, $this->balance('1000'));
    }

    public function test_zero_skips_a_fund_and_both_zero_is_refused(): void
    {
        $this->post(route('admin.community.contributions.store'), $this->payment(['dharura_amount' => 0]));
        $this->assertSame(['jamii'], CommunityContribution::pluck('fund')->all());

        $this->post(route('admin.community.contributions.store'), $this->payment(['jamii_amount' => 0, 'dharura_amount' => 0]))
            ->assertSessionHas('toast_tone', 'error');
        $this->assertSame(1, CommunityContribution::count());
    }

    public function test_standard_amounts_can_be_raised_from_the_jamii_page(): void
    {
        $this->put(route('admin.community.rates'), ['jamii' => 7_000, 'dharura' => 6_000])->assertSessionHasNoErrors();

        $this->get(route('admin.community.index'))->assertSee(money(7_000))->assertSee(money(6_000));
    }

    public function test_reversing_a_contribution_takes_it_out_of_the_fund_and_the_books(): void
    {
        $this->post(route('admin.community.contributions.store'), $this->payment());
        $jamii = CommunityContribution::where('fund', 'jamii')->firstOrFail();

        $this->post(route('admin.community.contributions.reverse', $jamii), ['reason' => 'Wrong member'])->assertSessionHasNoErrors();
        $this->post(route('admin.community.contributions.reverse', $jamii), ['reason' => 'Again'])->assertSessionHas('toast_tone', 'error');

        $this->assertSame('reversed', $jamii->fresh()->status);
        $this->assertSame(0, $this->balance('2200'));
        $this->assertSame(5_000, $this->balance('2300'));
        $this->assertSame(5_000, $this->balance('1000'));
    }

    public function test_the_month_view_marks_who_has_paid(): void
    {
        $other = Member::factory()->create(['organization_id' => $this->organization->id]);
        $this->post(route('admin.community.contributions.store'), $this->payment());

        $html = $this->get(route('admin.community.index', ['month' => now()->format('Y-m')]))->assertOk()->getContent();

        $this->assertStringContainsString(t('community.paidInFull', ['paid' => 1, 'total' => 2]), $html);
        $this->assertStringContainsString(e($other->full_name), $html);
    }

    public function test_a_member_sees_this_months_jamii_and_dharura_at_home(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole(Role::findOrCreate('member', 'web'));
        $this->member->update(['user_id' => $user->id]);
        $this->post(route('admin.community.contributions.store'), $this->payment(['dharura_amount' => 0]));

        $this->actingAs($user)->get(route('member.home'))
            ->assertOk()
            ->assertSee(t('member.communityThisMonth', ['month' => now()->translatedFormat('F')]))
            ->assertSee(t('member.dueAmount', ['amount' => num(5_000)]));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payment(array $overrides = []): array
    {
        return ['member_id' => $this->member->id, 'period' => now()->format('Y-m'), 'paid_on' => now()->toDateString(), 'jamii_amount' => 5_000, 'dharura_amount' => 5_000, ...$overrides];
    }

    private function balance(string $code): int
    {
        return (int) Account::where('organization_id', $this->organization->id)->where('account_code', $code)->value('balance');
    }
}
