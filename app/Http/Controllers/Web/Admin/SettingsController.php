<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\BeemSmsService;
use App\Services\MessagingSettings;
use App\Support\Audit;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['organization', 'modules', 'financial', 'messaging'], true) ? $request->get('tab') : 'organization';
        $org = app('kikoba.org');
        $config = Setting::where('organization_id', $org->id)->pluck('value', 'key');

        $messaging = app(MessagingSettings::class);

        return view('admin.settings.index', [
            'tab' => $tab,
            'org' => $org,
            'config' => $config,
            'messaging' => $messaging->forForm($org->id),
            'smsReady' => $messaging->smsReady($org->id),
            'mailReady' => $messaging->mailReady($org->id),
        ]);
    }

    public function messaging(Request $request, MessagingSettings $messaging)
    {
        $data = $request->validate([
            'sms_enabled' => ['boolean'],
            'beem_api_key' => ['nullable', 'string', 'max:255'],
            'beem_secret_key' => ['nullable', 'string', 'max:255'],
            'beem_sender_id' => ['nullable', 'string', 'max:11'],
            'mail_enabled' => ['boolean'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', 'in:ssl,tls'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ]);
        $data['sms_enabled'] = $request->boolean('sms_enabled');
        $data['mail_enabled'] = $request->boolean('mail_enabled');
        $messaging->save($request, app('kikoba.org')->id, $data);
        Audit::log($request, 'UPDATE_MESSAGING_SETTINGS', 'Setting', null, null, [
            'sms_enabled' => $data['sms_enabled'], 'mail_enabled' => $data['mail_enabled'],
            'beem_keys_changed' => filled($data['beem_api_key'] ?? null) || filled($data['beem_secret_key'] ?? null),
            'mail_password_changed' => filled($data['mail_password'] ?? null),
        ]);

        return redirect()->route('admin.settings.index', ['tab' => 'messaging'])->with('toast', t('settings.saved'));
    }

    public function testSms(Request $request, MessagingSettings $messaging, BeemSmsService $sms)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $orgId = app('kikoba.org')->id;
        if (! $messaging->smsReady($orgId)) {
            return back()->with('toast', t('messaging.smsNotReady'))->with('toast_tone', 'error');
        }
        $messaging->apply($orgId);
        $ok = $sms->send($data['phone'], t('messaging.testSmsText', ['org' => app('kikoba.org')->name]));

        return back()->with('toast', $ok ? t('messaging.testSmsSent') : t('messaging.testFailed'))->with('toast_tone', $ok ? 'success' : 'error');
    }

    public function testEmail(Request $request, MessagingSettings $messaging)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $orgId = app('kikoba.org')->id;
        if (! $messaging->mailReady($orgId)) {
            return back()->with('toast', t('messaging.mailNotReady'))->with('toast_tone', 'error');
        }
        $messaging->apply($orgId);
        try {
            Mail::raw(t('messaging.testEmailText', ['org' => app('kikoba.org')->name]), fn ($m) => $m->to($data['email'])->subject(t('messaging.testEmailSubject')));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('toast', t('messaging.testFailed').' '.str($e->getMessage())->limit(120))->with('toast_tone', 'error');
        }

        return back()->with('toast', t('messaging.testEmailSent'));
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
        // Only settings the system actually reads; anything else posted is ignored.
        $data = $request->validate([
            'share_price' => ['required', 'integer', 'min:1'],
        ]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => $key],
                ['value' => (string) $value, 'updated_by' => $request->user()->id],
            );
        }
        Audit::log($request, 'UPDATE_SETTINGS', 'Setting', null);

        return back()->with('toast', t('settings.saved'));
    }
}
