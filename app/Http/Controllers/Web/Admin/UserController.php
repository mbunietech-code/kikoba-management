<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('organization_id', app('kikoba.org')->id)->with('roles')->orderBy('name')->get();

        return view('admin.users.index', ['users' => $users, 'assignableRoles' => $this->assignableRoles($request)]);
    }

    /**
     * Roles the current user may hand out. Only a super admin can create or edit another super admin.
     *
     * @return array<int, string>
     */
    private function assignableRoles(Request $request): array
    {
        $roles = ['admin', 'treasurer', 'accountant', 'loan_officer', 'member'];

        return $request->user()->hasRole('super_admin') ? ['super_admin', ...$roles] : $roles;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', Rule::in($this->assignableRoles($request))],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $user = User::create([
            'organization_id' => app('kikoba.org')->id,
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
            'password' => $data['password'], 'status' => 'active',
        ]);
        $user->assignRole($data['role']);
        Audit::log($request, 'CREATE_USER', 'User', $user->id);

        return back()->with('toast', t('common.create').' ✓');
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('super_admin') && ! $request->user()->hasRole('super_admin')) {
            return back()->with('toast', t('users.protected'))->with('toast_tone', 'error');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'status' => ['required', 'in:active,suspended'],
            'role' => ['required', Rule::in($this->assignableRoles($request))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        if ($user->id === $request->user()->id && ($data['status'] !== 'active' || $data['role'] !== $user->primaryRole())) {
            return back()->with('toast', t('users.cannotDemoteSelf'))->with('toast_tone', 'error');
        }
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'status' => $data['status']]);
        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }
        if ($data['status'] !== 'active') {
            $user->tokens()->delete();
        }
        $user->syncRoles([$data['role']]);
        Audit::log($request, 'UPDATE_USER', 'User', $user->id, null, ['role' => $data['role'], 'status' => $data['status'], 'password_reset' => ! empty($data['password'])]);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id || $user->hasRole('super_admin')) {
            return back()->with('toast', t('users.protected'))->with('toast_tone', 'error');
        }
        $user->tokens()->delete();
        $user->delete();
        Audit::log($request, 'DELETE_USER', 'User', $user->id);

        return back()->with('toast', t('common.deleted'));
    }
}
