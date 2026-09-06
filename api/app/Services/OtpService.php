<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(private readonly BeemSmsService $sms) {}

    /**
     * Issue an OTP for a user and dispatch it via SMS.
     */
    public function issue(User $user, string $purpose = 'login'): OtpCode
    {
        $length = config('services.auth.otp_length');
        $ttl = config('services.auth.otp_ttl');

        // invalidate previous unconsumed codes for this purpose
        OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'destination' => $user->phone ?? $user->email,
            'channel' => $user->phone ? 'sms' : 'email',
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        if ($user->phone) {
            $this->sms->send($user->phone, "Benja Kikoba: your verification code is {$code}. Valid for ".intdiv($ttl, 60).' minutes.');
        }

        // expose the plain code only in non-production for testing
        if (! app()->isProduction()) {
            $otp->setAttribute('plain_code', $code);
        }

        return $otp;
    }

    public function verify(string $destination, string $code, string $purpose = 'login'): ?User
    {
        $otp = OtpCode::with('user')
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return null;
        }

        if ($otp->attempts >= 5) {
            $otp->update(['consumed_at' => now()]);

            return null;
        }

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp->increment('attempts');

            return null;
        }

        $otp->update(['consumed_at' => now()]);

        return $otp->user;
    }

    public function issueForDestination(string $destination, string $channel = 'sms', string $purpose = 'verify'): string
    {
        $length = config('services.auth.otp_length');
        $ttl = config('services.auth.otp_ttl');
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        OtpCode::create([
            'destination' => $destination,
            'channel' => $channel,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        if ($channel === 'sms') {
            $this->sms->send($destination, "Benja Kikoba: your code is {$code}.");
        }

        return $code;
    }
}
