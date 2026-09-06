<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $active = $request->get('role', 'admin');
        $roles = Role::with('permissions')->whereNot('name', 'super_admin')->get();
        $permissions = Permission::orderBy('name')->pluck('name');
        $current = Role::where('name', $active)->with('permissions')->first() ?? $roles->first();
        $counts = $roles->mapWithKeys(fn ($r) => [$r->name => User::role($r->name)->where('organization_id', app('kikoba.org')->id)->count()]);

        return view('admin.roles.index', compact('roles', 'permissions', 'current', 'active', 'counts'));
    }

    public function update(Request $request, string $role)
    {
        $data = $request->validate(['permissions' => ['array']]);
        Role::findByName($role)->syncPermissions($data['permissions'] ?? []);
        Audit::log($request, 'UPDATE_ROLE', 'Role', $role);

        return redirect()->route('admin.roles.index', ['role' => $role])->with('toast', t('settings.saved'));
    }
}
