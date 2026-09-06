<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('organization_id', app('kikoba.org')->id)->with('roles')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
            'role' => ['required', 'exists:roles,name'],
        ]);
        $user = User::create([
            'organization_id' => app('kikoba.org')->id,
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
            'password' => bin2hex(random_bytes(8)), 'status' => 'active',
        ]);
        $user->assignRole($data['role']);
        Audit::log($request, 'CREATE_USER', 'User', $user->id);

        return back()->with('toast', t('common.create').' ✓');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'status' => ['required', 'in:active,suspended'],
            'role' => ['required', 'exists:roles,name'],
        ]);
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'status' => $data['status']]);
        $user->syncRoles([$data['role']]);
        Audit::log($request, 'UPDATE_USER', 'User', $user->id);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id || $user->hasRole('super_admin')) {
            return back()->with('toast', 'Protected account');
        }
        $user->tokens()->delete();
        $user->delete();
        Audit::log($request, 'DELETE_USER', 'User', $user->id);

        return back()->with('toast', t('common.deleted'));
    }
}
