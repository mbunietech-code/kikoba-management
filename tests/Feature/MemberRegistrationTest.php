<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MemberRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        Storage::fake('local');
        $this->organization = Organization::create(['name' => 'Umoja Test', 'code' => 'TEST']);
        $this->staff = User::factory()->create(['organization_id' => $this->organization->id]);
        foreach (['members.create', 'members.update', 'members.view'] as $permission) {
            $this->staff->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($this->staff);
    }

    public function test_form_includes_complete_registration_fields(): void
    {
        $response = $this->get(route('admin.members.create'))->assertOk();
        foreach (['nida_number', 'nationality', 'marital_status', 'alternative_phone', 'region', 'district', 'ward', 'street', 'occupation', 'employer', 'next_of_kin', 'next_of_kin_phone', 'next_of_kin_relationship', 'next_of_kin_address', 'registration_date', 'photo'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
    }

    public function test_complete_details_are_saved_and_available_for_viewing_and_editing(): void
    {
        $this->post(route('admin.members.store'), $this->details())->assertSessionHasNoErrors()->assertRedirect();
        $member = Member::firstOrFail();
        $this->assertSame('19900101123450000101', $member->nida_number);
        $this->assertSame('Sibling', $member->next_of_kin_relationship);
        $this->assertSame('Shop owner', $member->occupation);
        $this->assertSame('2020-01-02', $member->registration_date->toDateString());
        $this->assertSame($this->organization->id, $member->organization_id);
        $this->assertSame(0, SavingsAccount::where('member_id', $member->id)->value('balance'), 'a savings account is opened on registration');
        $this->get(route('admin.members.edit', $member))->assertOk()->assertSee('19900101123450000101');
        $this->get(route('admin.members.show', $member))->assertOk()->assertSee('Dar es Salaam')->assertSee('Sibling');
        $this->assertArrayNotHasKey('nida_number', $member->toArray());
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE_MEMBER', 'entity_id' => $member->id]);
    }

    public function test_minimal_legacy_registration_still_works(): void
    {
        $this->post(route('admin.members.store'), ['full_name' => 'Legacy Member', 'phone' => '0712345678', 'status' => 'active'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $member = Member::firstOrFail();
        $this->assertNull($member->nida_number);
        $this->assertSame(now()->toDateString(), $member->registration_date->toDateString());
    }

    public function test_invalid_identity_dates_and_incomplete_kin_are_rejected(): void
    {
        $this->post(route('admin.members.store'), [...$this->details(),
            'nida_number' => 'invalid', 'date_of_birth' => now()->addDay()->toDateString(),
            'registration_date' => '1980-01-01', 'next_of_kin' => '',
        ])->assertSessionHasErrors(['nida_number', 'date_of_birth', 'registration_date', 'next_of_kin']);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_duplicate_nida_is_blocked_but_the_owner_can_update(): void
    {
        $member = $this->member(['nida_number' => '19900101123450000101']);
        $this->post(route('admin.members.store'), $this->details())->assertSessionHasErrors('nida_number');
        $this->put(route('admin.members.update', $member), [...$this->details(), 'occupation' => 'Teacher'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Teacher', $member->fresh()->occupation);
        $this->assertDatabaseCount('members', 1);
    }

    public function test_photo_is_private_and_only_removed_explicitly(): void
    {
        $photo = UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aGxkAAAAASUVORK5CYII='));
        $this->post(route('admin.members.store'), [...$this->details(), 'photo' => $photo])->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $path = $member->photo_path;
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.members.photo', $member))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertArrayNotHasKey('photo_path', $member->toArray());
        $this->put(route('admin.members.update', $member), $this->details())->assertSessionHasNoErrors();
        $this->assertSame($path, $member->fresh()->photo_path);
        $this->put(route('admin.members.update', $member), [...$this->details(), 'remove_photo' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($member->fresh()->photo_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_invalid_uploads_are_rejected_before_any_record_is_written(): void
    {
        foreach ([UploadedFile::fake()->create('script.php', 1, 'text/plain'), UploadedFile::fake()->create('big.jpg', 2049, 'image/jpeg')] as $photo) {
            $this->post(route('admin.members.store'), [...$this->details(), 'photo' => $photo])->assertSessionHasErrors('photo');
        }
        $this->assertDatabaseCount('members', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_another_organizations_details_and_photos_cannot_be_accessed(): void
    {
        $other = Organization::create(['name' => 'Other group', 'code' => 'OTHER']);
        $member = $this->member(['organization_id' => $other->id]);
        $this->get(route('admin.members.show', $member))->assertNotFound();
        $this->get(route('admin.members.edit', $member))->assertNotFound();
        $this->get(route('admin.members.photo', $member))->assertNotFound();
        $this->put(route('admin.members.update', $member), $this->details())->assertForbidden();
    }

    public function test_registration_requires_permission(): void
    {
        $this->staff->syncPermissions([]);
        $this->post(route('admin.members.store'), $this->details())->assertForbidden();
        $this->assertDatabaseCount('members', 0);
    }

    public function test_multiple_next_of_kin_are_saved_displayed_and_can_be_removed(): void
    {
        $kin = [
            ['name' => 'Second Kin', 'phone' => '0712345681', 'relationship' => 'Parent', 'address' => 'Kin Home'],
            ['name' => 'Third Kin', 'phone' => '0712345682', 'relationship' => 'Sibling', 'address' => 'Another Home'],
        ];
        $this->post(route('admin.members.store'), [...$this->details(), 'kin_editor' => '1', 'additional_next_of_kin' => $kin])
            ->assertSessionHasNoErrors()->assertRedirect();
        $member = Member::firstOrFail();
        $this->assertSame('Test Kin', $member->next_of_kin);
        $this->assertSame($kin, $member->additional_next_of_kin);
        $this->get(route('admin.members.edit', $member))->assertOk()->assertSee(t('members.addKin'))->assertSee('Second Kin');
        $this->get(route('admin.members.show', $member))->assertOk()->assertSee('Second Kin')->assertSee('Third Kin');
        $this->put(route('admin.members.update', $member), [...$this->details(), 'kin_editor' => '1', 'additional_next_of_kin' => [$kin[1]]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$kin[1]], $member->fresh()->additional_next_of_kin);
        $this->put(route('admin.members.update', $member), [...$this->details(), 'kin_editor' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([], $member->fresh()->additional_next_of_kin);
        $this->assertSame('Test Kin', $member->next_of_kin);
    }

    public function test_legacy_updates_do_not_erase_additional_next_of_kin(): void
    {
        $kin = [['name' => 'Saved Kin', 'phone' => '0712345681']];
        $member = $this->member(['additional_next_of_kin' => $kin]);
        $this->put(route('admin.members.update', $member), $this->details())->assertSessionHasNoErrors();
        $this->assertSame($kin, $member->fresh()->additional_next_of_kin);
    }

    public function test_additional_next_of_kin_must_have_names_and_allow_only_expected_fields(): void
    {
        $this->post(route('admin.members.store'), [...$this->details(), 'additional_next_of_kin' => [
            ['name' => '', 'phone' => '0712345681'],
            ['name' => 'Another Kin', 'unexpected' => 'not allowed'],
        ]])->assertSessionHasErrors(['additional_next_of_kin.0.name', 'additional_next_of_kin.1']);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_empty_additional_rows_are_ignored_and_excessive_rows_are_rejected(): void
    {
        $this->post(route('admin.members.store'), [...$this->details(), 'additional_next_of_kin' => [['name' => '', 'phone' => '']]])
            ->assertSessionHasNoErrors();
        $this->assertSame([], Member::firstOrFail()->additional_next_of_kin);
        $this->post(route('admin.members.store'), [...$this->details(), 'nida_number' => null, 'additional_next_of_kin' => array_fill(0, 21, ['name' => 'Kin'])])
            ->assertSessionHasErrors('additional_next_of_kin');
        $this->assertDatabaseCount('members', 1);
    }

    public function test_validation_errors_preserve_added_kin_and_malformed_lists_do_not_break_the_form(): void
    {
        $this->post(route('admin.members.store'), [...$this->details(), 'email' => 'invalid',
            'additional_next_of_kin' => [['name' => 'Retry Kin', 'phone' => '0712345681']],
        ])->assertSessionHasErrors('email');
        $this->get(route('admin.members.create'))->assertOk()->assertSee('Retry Kin');
        $this->post(route('admin.members.store'), [...$this->details(), 'additional_next_of_kin' => 'invalid'])
            ->assertSessionHasErrors('additional_next_of_kin');
        $this->get(route('admin.members.create'))->assertOk();
    }

    public function test_adding_kin_column_preserves_primary_kin_and_other_member_details(): void
    {
        $migration = require database_path('migrations/2026_11_02_072731_add_additional_next_of_kin_to_members_table.php');
        $migration->down();
        $member = $this->member(['next_of_kin' => 'Existing Kin', 'next_of_kin_phone' => '0710000000']);
        $before = DB::table('members')->where('id', $member->id)->first();
        $migration->up();
        $after = DB::table('members')->where('id', $member->id)->first();
        foreach ((array) $before as $field => $value) {
            $this->assertSame($value, $after->$field);
        }
        $this->assertNull($after->additional_next_of_kin);
    }

    public function test_additive_migration_preserves_member_and_financial_records(): void
    {
        $migration = require database_path('migrations/2026_11_02_071050_add_registration_details_to_members_table.php');
        $migration->down();
        $member = $this->member(['next_of_kin' => 'Existing Kin', 'next_of_kin_phone' => '0710000000']);
        $savings = SavingsAccount::create(['organization_id' => $this->organization->id, 'member_id' => $member->id,
            'account_number' => 'SAV-LEGACY', 'balance' => 450000, 'opened_at' => '2020-01-01']);
        $before = DB::table('members')->where('id', $member->id)->first();
        $migration->up();
        $after = DB::table('members')->where('id', $member->id)->first();
        foreach ((array) $before as $field => $value) {
            $this->assertSame($value, $after->$field);
        }
        $this->assertNull($after->nida_number);
        $this->assertSame(450000, $savings->fresh()->balance);
    }

    /** @return array<string, string> */
    private function details(): array
    {
        return ['full_name' => 'Test Member', 'phone' => '0712345678', 'email' => 'member@example.test',
            'nida_number' => '19900101-12345-00001-01', 'gender' => 'female', 'date_of_birth' => '1990-01-01',
            'nationality' => 'Tanzanian', 'marital_status' => 'married', 'alternative_phone' => '0712345679',
            'address' => 'Test residence', 'region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Test Ward',
            'street' => 'Test Street', 'occupation' => 'Shop owner', 'employer' => 'Test Shop',
            'next_of_kin' => 'Test Kin', 'next_of_kin_phone' => '0712345680', 'next_of_kin_relationship' => 'Sibling',
            'next_of_kin_address' => 'Kin address', 'registration_date' => '2020-01-02', 'status' => 'active'];
    }

    /** @param array<string, mixed> $attributes */
    private function member(array $attributes = []): Member
    {
        return Member::factory()->create(['organization_id' => $this->organization->id, ...$attributes]);
    }
}
