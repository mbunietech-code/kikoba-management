<?php

namespace App\Services;

use App\Mail\MemberLoginDetails;
use App\Models\Member;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Gives a member a sign-in to the member portal — username is their phone
 * number, password a temporary one they must change at first sign-in — and
 * sends the details by SMS and e-mail when those are set up under Settings.
 */
class MemberLoginService
{
    private const PASSWORD_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(
        private readonly MessagingSettings $messaging,
        private readonly BeemSmsService $sms,
    ) {}

    /**
     * Create the member's login, or give an existing member login a new temporary password.
     *
     * Status is created, reset or no_phone. An officer who is also a member gets a
     * member login of their own, separate from their staff login.
     *
     * @return array{status: string, name: string, username: string, password: ?string, sms: ?bool, email: ?bool}
     */
    public function provision(Request $request, Member $member): array
    {
        $username = $this->localPhone((string) $member->phone);
        $result = ['status' => 'created', 'name' => $member->full_name, 'username' => $username, 'password' => null, 'sms' => null, 'email' => null];

        if (blank($member->phone)) {
            return ['status' => 'no_phone'] + $result;
        }

        $user = $member->user_id ? User::find($member->user_id) : null;
        $user ??= User::whereIn('phone', phone_variants((string) $member->phone))
            ->when(filled($member->email), fn ($q) => $q->orWhere('email', $member->email))->first();

        // An officer who is also a member gets a separate portal login; the staff login stays for the admin side.
        if ($user?->isStaff()) {
            $user = null;
        }

        $password = $this->temporaryPassword();

        DB::transaction(function () use ($member, &$user, $password, &$result) {
            if ($user) {
                $user->update(['password' => $password, 'must_change_password' => true, 'status' => 'active']);
                $result['status'] = 'reset';
            } else {
                $phone = $this->internationalPhone((string) $member->phone);
                $user = User::create([
                    'organization_id' => $member->organization_id,
                    'name' => $member->full_name,
                    'email' => filled($member->email) && ! User::withTrashed()->where('email', $member->email)->exists()
                        ? $member->email
                        : strtolower($member->member_number).'@members.invalid',
                    // A staff account may already hold the number; sign-in then finds this account through the member record.
                    'phone' => User::withTrashed()->whereIn('phone', phone_variants($phone))->exists() ? null : $phone,
                    'password' => $password,
                    'must_change_password' => true,
                    'status' => 'active',
                ]);
                $user->assignRole(Role::findOrCreate('member', 'web'));
            }
            $member->update(['user_id' => $user->id]);
        });

        $result['password'] = $password;
        $orgName = app()->bound('kikoba.org') ? app('kikoba.org')->name : config('app.name');
        $loginUrl = route('login');

        if ($this->messaging->smsReady($member->organization_id)) {
            $result['sms'] = $this->sms->send((string) $member->phone, __('k.memberLogin.sms', [
                'org' => $orgName, 'url' => $loginUrl, 'username' => $username, 'password' => $password,
            ], 'sw'));
        }

        if ($this->messaging->mailReady($member->organization_id) && filled($member->email)) {
            try {
                Mail::to($member->email)->send(new MemberLoginDetails($member->full_name, $orgName, $username, $password, $loginUrl));
                $result['email'] = true;
            } catch (Throwable $e) {
                report($e);
                $result['email'] = false;
            }
        }

        Audit::log($request, $result['status'] === 'reset' ? 'RESET_MEMBER_LOGIN' : 'CREATE_MEMBER_LOGIN', 'Member', $member->id, null, [
            'username' => $username, 'sms_sent' => $result['sms'], 'email_sent' => $result['email'],
        ]);

        return $result;
    }

    private function temporaryPassword(): string
    {
        $alphabet = self::PASSWORD_ALPHABET;
        $chars = '';
        for ($i = 0; $i < 8; $i++) {
            $chars .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return substr($chars, 0, 4).'-'.substr($chars, 4);
    }

    /** 0716123456 style — what members type to sign in. */
    private function localPhone(string $phone): string
    {
        return collect(phone_variants($phone))->first(fn (string $v) => preg_match('/^0\d{9}$/', $v)) ?? trim($phone);
    }

    /** +255716123456 style — how the login is stored. */
    private function internationalPhone(string $phone): string
    {
        return collect(phone_variants($phone))->first(fn (string $v) => str_starts_with($v, '+255')) ?? trim($phone);
    }
}
