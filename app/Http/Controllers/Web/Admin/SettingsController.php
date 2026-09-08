<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Setting;
use App\Support\Audit;
use App\Support\Features;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'organization');
        $org = app('kikoba.org');
        $config = Setting::where('organization_id', $org->id)->pluck('value', 'key');

        return view('admin.settings.index', compact('tab', 'org', 'config'));
    }

    public function organization(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);
        $org = Organization::findOrFail(app('kikoba.org')->id);
        $before = $org->only(array_keys($data));
        $org->update($data);
        Audit::log($request, 'UPDATE_ORGANIZATION', 'Organization', $org->id, $before, $data);

        return back()->with('toast', t('settings.saved'));
    }

    public function modules(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        foreach (array_keys(Features::MODULES) as $key) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => "module_{$key}"],
                ['value' => $request->boolean("module_{$key}") ? '1' : '0', 'updated_by' => $request->user()->id],
            );
        }
        Audit::log($request, 'UPDATE_MODULES', 'Setting', null);

        return back()->with('toast', t('settings.saved'));
    }

    public function config(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        foreach ($request->except('_token', '_method') as $key => $value) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => $key],
                ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'updated_by' => $request->user()->id],
            );
        }
        Audit::log($request, 'UPDATE_SETTINGS', 'Setting', null);

        return back()->with('toast', t('settings.saved'));
    }
}
