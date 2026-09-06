<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class OrganizationController extends ApiController
{
    /** Public — used by the login screen / app shell before auth. */
    public function current()
    {
        $org = Organization::query()->orderBy('created_at')->first();

        return ApiResponse::ok([
            'id' => $org?->id,
            'name' => $org?->name ?? 'Benja Kikoba',
            'currency' => $org?->currency ?? 'TZS',
            'logoUrl' => $org?->logo_url,
            'registrationNumber' => $org?->registration_number,
            'phone' => $org?->phone,
            'email' => $org?->email,
            'address' => $org?->address,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'logo_url' => ['nullable', 'string'],
        ]);

        $org = Organization::findOrFail($this->orgId($request));
        $before = $org->only(array_keys($data));
        $org->update($data);

        Audit::log($request, 'UPDATE_ORGANIZATION', 'Organization', $org->id, $before, $data);

        return $this->current();
    }
}
