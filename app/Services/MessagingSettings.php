<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * SMS (Beem Africa) and e-mail (SMTP, e.g. Hostinger) credentials entered by an
 * administrator under Settings. Secrets are stored encrypted with the app key
 * and are never sent back to the browser; apply() loads them into config so
 * BeemSmsService and Laravel Mail use them.
 */
class MessagingSettings
{
    private const PLAIN = ['sms_enabled', 'beem_sender_id', 'mail_enabled', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name'];

    private const SECRET = ['beem_api_key', 'beem_secret_key', 'mail_password'];

    /**
     * Stored values; secrets are reported only as set / not set.
     *
     * @return array<string, string|bool|null>
     */
    public function forForm(string $orgId): array
    {
        $values = $this->all($orgId);
        foreach (self::SECRET as $key) {
            $values[$key.'_set'] = filled($values[$key] ?? null);
            unset($values[$key]);
        }

        return $values + [
            'mail_host' => 'smtp.hostinger.com',
            'mail_port' => '465',
            'mail_encryption' => 'ssl',
        ];
    }

    /**
     * @param  array<string, mixed>  $data  blank secrets keep the stored value
     */
    public function save(Request $request, string $orgId, array $data): void
    {
        foreach (self::PLAIN as $key) {
            if (array_key_exists($key, $data)) {
                $this->put($request, $orgId, $key, is_bool($data[$key]) ? ($data[$key] ? '1' : '0') : (string) $data[$key]);
            }
        }
        foreach (self::SECRET as $key) {
            if (filled($data[$key] ?? null)) {
                $this->put($request, $orgId, $key, Crypt::encryptString((string) $data[$key]));
            }
        }
    }

    public function smsReady(string $orgId): bool
    {
        $v = $this->all($orgId);

        return ($v['sms_enabled'] ?? '0') === '1' && filled($v['beem_api_key'] ?? null) && filled($v['beem_secret_key'] ?? null);
    }

    public function mailReady(string $orgId): bool
    {
        $v = $this->all($orgId);

        return ($v['mail_enabled'] ?? '0') === '1' && filled($v['mail_host'] ?? null) && filled($v['mail_username'] ?? null)
            && filled($v['mail_password'] ?? null) && filled($v['mail_from_address'] ?? null);
    }

    /**
     * Point SMS and mail configuration at the group's own credentials, when they are complete.
     */
    public function apply(string $orgId): void
    {
        $v = $this->all($orgId);

        if ($this->smsReady($orgId)) {
            config([
                'services.beem.enabled' => true,
                'services.beem.key' => $v['beem_api_key'],
                'services.beem.secret' => $v['beem_secret_key'],
                'services.beem.sender_id' => $v['beem_sender_id'] ?: 'INFO',
            ]);
        }

        if ($this->mailReady($orgId)) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $v['mail_host'],
                'mail.mailers.smtp.port' => (int) ($v['mail_port'] ?: 465),
                'mail.mailers.smtp.scheme' => ($v['mail_encryption'] ?? 'ssl') === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.username' => $v['mail_username'],
                'mail.mailers.smtp.password' => $v['mail_password'],
                'mail.from.address' => $v['mail_from_address'],
                'mail.from.name' => $v['mail_from_name'] ?: config('app.name'),
            ]);
        }
    }

    /**
     * @return array<string, string|null> decrypted values
     */
    private function all(string $orgId): array
    {
        return Setting::where('organization_id', $orgId)
            ->whereIn('key', [...self::PLAIN, ...self::SECRET])
            ->pluck('value', 'key')
            ->map(function (?string $value, string $key) {
                if (! in_array($key, self::SECRET, true) || blank($value)) {
                    return $value;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    return null; // stored with a different app key
                }
            })
            ->all();
    }

    private function put(Request $request, string $orgId, string $key, string $value): void
    {
        Setting::updateOrCreate(
            ['organization_id' => $orgId, 'key' => $key],
            ['value' => $value, 'updated_by' => $request->user()->id],
        );
    }
}
