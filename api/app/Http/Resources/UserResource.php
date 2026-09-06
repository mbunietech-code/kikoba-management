<?php

namespace App\Http\Resources;

class UserResource extends ApiResource
{
    public function fields($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'role' => $this->whenLoaded('roles', fn () => $this->roles->first()?->name),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when($request->routeIs('auth.me'), fn () => $this->getAllPermissions()->pluck('name')),
            'member_id' => $this->whenLoaded('member', fn () => $this->member?->id),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'currency' => $this->organization->currency,
            ]),
            'last_login_at' => $this->last_login_at,
        ];
    }
}
