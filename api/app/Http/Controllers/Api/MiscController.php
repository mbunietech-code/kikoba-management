<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MiscController extends ApiController
{
    /* ---------------- notifications ---------------- */
    public function notifications(Request $request)
    {
        $q = Notification::where('organization_id', $this->orgId($request))->latest('created_at');

        return $this->paginate($q, $request, fn (Notification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'message' => $n->message,
            'type' => $n->type,
            'channel' => $n->channel,
            'read' => $n->read_at !== null,
            'createdAt' => $n->created_at?->toIso8601String(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('organization_id', $this->orgId($request))->whereNull('read_at')->update(['read_at' => now()]);

        return ApiResponse::message('All notifications marked read');
    }

    public function sendAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'in:in_app,sms,email,push,whatsapp'],
        ]);
        $n = Notification::create([...$data, 'organization_id' => $this->orgId($request), 'type' => 'announcement']);
        Audit::log($request, 'SEND_ANNOUNCEMENT', 'Notification', $n->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($n))->resolve(), 'Announcement sent');
    }

    /* ---------------- settings ---------------- */
    public function settings(Request $request)
    {
        $settings = Setting::where('organization_id', $this->orgId($request))->get()
            ->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        return ApiResponse::ok($settings);
    }

    public function updateSettings(Request $request)
    {
        $orgId = $this->orgId($request);
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => $key],
                ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'updated_by' => $request->user()->id],
            );
        }
        Audit::log($request, 'UPDATE_SETTINGS', 'Setting', null, null, $request->all());

        return ApiResponse::message('Settings saved');
    }

    /* ---------------- users & roles ---------------- */
    public function users(Request $request)
    {
        return $this->items(
            User::where('organization_id', $this->orgId($request))->with('roles')->get(),
            fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->roles->first()?->name,
                'status' => $u->status,
                'lastLoginAt' => $u->last_login_at?->toIso8601String(),
            ],
        );
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
            'role' => ['required', 'exists:roles,name'],
        ]);
        $user = User::create([
            'organization_id' => $this->orgId($request),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => bin2hex(random_bytes(8)),
            'status' => 'active',
        ]);
        $user->assignRole($data['role']);
        Audit::log($request, 'CREATE_USER', 'User', $user->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($user))->resolve(), 'User created');
    }

    public function roles(Request $request)
    {
        $roles = Role::with('permissions')->get()->map(fn (Role $r) => [
            'name' => $r->name,
            'permissions' => $r->permissions->pluck('name'),
            'members' => User::role($r->name)->where('organization_id', $this->orgId($request))->count(),
        ]);

        return ApiResponse::ok([
            'roles' => $roles,
            'allPermissions' => \Spatie\Permission\Models\Permission::pluck('name'),
        ]);
    }

    public function updateRole(Request $request, string $name)
    {
        $data = $request->validate(['permissions' => ['required', 'array']]);
        $role = Role::findByName($name);
        $role->syncPermissions($data['permissions']);
        Audit::log($request, 'UPDATE_ROLE', 'Role', $role->id);

        return ApiResponse::message('Role updated');
    }

    /* ---------------- audit ---------------- */
    public function auditLogs(Request $request)
    {
        $q = AuditLog::where('organization_id', $this->orgId($request))
            ->with('user:id,name,email')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->latest('created_at');

        $this->applySearch($q, $request, ['action', 'entity', 'entity_id']);

        return $this->paginate($q, $request, fn (AuditLog $a) => [
            'id' => $a->id,
            'user' => $a->user?->email ?? 'system',
            'action' => $a->action,
            'entity' => $a->entity,
            'entityId' => $a->entity_id,
            'oldValue' => $a->old_values['value'] ?? null,
            'newValue' => $a->new_values['value'] ?? null,
            'ipAddress' => $a->ip_address,
            'createdAt' => $a->created_at?->toIso8601String(),
        ]);
    }
}
