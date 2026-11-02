<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveMemberRequest;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Services\MemberLoginService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::where('organization_id', app('kikoba.org')->id)
            ->withSum('shares as shares_value', 'total_value')
            ->with('savingsAccount:id,member_id,balance')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->gender, fn ($q, $g) => $q->where('gender', $g))
            ->when($request->filled('group'), fn ($q) => $request->group === '__none__'
                ? $q->where(fn ($w) => $w->whereNull('community_group')->orWhere('community_group', ''))
                : $q->where('community_group', $request->group))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->orderBy('member_number')
            ->paginate(15)->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function create()
    {
        return view('admin.members.form', ['member' => new Member]);
    }

    public function edit(Request $request, Member $member): View
    {
        $this->ensureOrganization($request, $member);

        return view('admin.members.form', compact('member'));
    }

    public function show(Request $request, Member $member): View
    {
        $this->ensureOrganization($request, $member);

        $member->load([
            'shares', 'savingsAccount', 'loans.product', 'insuranceAccount',
            'guaranteeing.loan', 'transactions' => fn ($q) => $q->latest()->limit(40),
            'savingsTransactions' => fn ($q) => $q->latest('created_at')->limit(40),
        ]);
        $tab = $request->get('tab', 'profile');

        return view('admin.members.show', compact('member', 'tab'));
    }

    public function store(SaveMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $orgId = $request->user()->organization_id;
        $data['organization_id'] = $orgId;
        $data['member_number'] = Member::nextNumber();
        $data['registration_date'] = $data['registration_date'] ?? now()->toDateString();
        $data['avatar_color'] = '#'.substr(md5($data['full_name']), 0, 6);

        $member = $this->persist($request, new Member, $data, 'CREATE_MEMBER');
        SavingsAccount::openFor($member);

        return redirect()->route('admin.members.show', $member)->with('toast', t('members.addMember').' ✓');
    }

    public function update(SaveMemberRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();
        $data['registration_date'] = $data['registration_date'] ?? $member->registration_date?->toDateString();
        $this->persist($request, $member, $data, 'UPDATE_MEMBER');

        return redirect()->route('admin.members.show', $member)->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, Member $member)
    {
        $this->ensureOrganization($request, $member);

        // SDD §39 — members tied to financial records are never removed; they
        // are deactivated instead so loans / savings / transactions stay intact.
        $hasRecords = $member->loans()->exists()
            || $member->shares()->exists()
            || (int) $member->savingsAccount?->balance > 0
            || $member->projectInvestments()->exists()
            || $member->insuranceAccount()->exists();

        if ($hasRecords) {
            $member->update(['status' => 'inactive']);
            Audit::log($request, 'DEACTIVATE_MEMBER', 'Member', $member->id, null, ['status' => 'inactive']);

            return redirect()->route('admin.members.show', $member)
                ->with('toast', t('members.deactivatedInstead'));
        }

        $member->delete();
        Audit::log($request, 'DELETE_MEMBER', 'Member', $member->id);

        return redirect()->route('admin.members.index')->with('toast', t('common.deleted'));
    }

    /**
     * Create (or renew) the member's portal login and send the details.
     */
    public function sendLogin(Request $request, Member $member, MemberLoginService $logins): RedirectResponse
    {
        $this->ensureOrganization($request, $member);
        $result = $logins->provision($request, $member);

        return back()->with('login_results', [$result]);
    }

    /**
     * Logins for every active member who does not have one yet.
     */
    public function sendMissingLogins(Request $request, MemberLoginService $logins): RedirectResponse
    {
        $members = Member::where('organization_id', $request->user()->organization_id)
            ->where('status', 'active')->whereNull('user_id')->orderBy('full_name')->get();
        $results = $members->map(fn (Member $m) => $logins->provision($request, $m))->all();

        return back()->with('login_results', $results)
            ->with('toast', t('memberLogin.bulkDone', ['count' => count($results)]));
    }

    public function photo(Request $request, Member $member): StreamedResponse
    {
        $this->ensureOrganization($request, $member);

        return $this->privateImage($member->photo_path);
    }

    public function kinPhoto(Request $request, Member $member): StreamedResponse
    {
        $this->ensureOrganization($request, $member);

        return $this->privateImage($member->next_of_kin_photo_path);
    }

    private function privateImage(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureOrganization(Request $request, Member $member): void
    {
        abort_unless($member->organization_id === $request->user()->organization_id, 404);
    }

    /**
     * Uploaded image field => [remove checkbox, column].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PHOTOS = [
        'photo' => ['remove_photo', 'photo_path'],
        'kin_photo' => ['remove_kin_photo', 'next_of_kin_photo_path'],
    ];

    /** @param array<string, mixed> $data */
    private function persist(SaveMemberRequest $request, Member $member, array $data, string $action): Member
    {
        $oldPhotos = [];
        $newPhotos = [];

        foreach (self::PHOTOS as $input => [$removeFlag, $column]) {
            unset($data[$input], $data[$removeFlag]);
            $oldPhotos[$column] = $member->$column;

            if ($request->hasFile($input)) {
                $stored = $request->file($input)->store('member-photos/'.$request->user()->organization_id, 'local');
                if (! $stored) {
                    Storage::disk('local')->delete($newPhotos);
                    throw new RuntimeException('Unable to store member photo.');
                }
                $data[$column] = $newPhotos[] = $stored;
            } elseif ($request->boolean($removeFlag)) {
                $data[$column] = null;
            }
        }

        try {
            DB::transaction(function () use ($request, $member, $data, $action): void {
                $member->fill($data)->save();
                Audit::log($request, $action, 'Member', $member->id);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPhotos);
            throw $exception;
        }

        foreach ($oldPhotos as $column => $oldPhoto) {
            if ($oldPhoto && $oldPhoto !== $member->$column) {
                Storage::disk('local')->delete($oldPhoto);
            }
        }

        return $member;
    }
}
