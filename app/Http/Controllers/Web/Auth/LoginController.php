<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show()
    {
        if (Auth::check()) {
            return $this->home();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['login'])->orWhere('phone', $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => t('auth.badCredentials')]);
        }
        if ($user->status !== 'active') {
            throw ValidationException::withMessages(['login' => 'Your account is not active.']);
        }

        if (config('services.beem.enabled') && $user->phone) {
            $otp = $this->otp->issue($user, 'login');
            $request->session()->put('otp_user', $user->id);
            $request->session()->put('otp_destination', $user->phone);
            $request->session()->put('otp_debug', $otp->getAttribute('plain_code'));

            return redirect()->route('verify-otp');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $this->home();
    }

    public function showOtp(Request $request)
    {
        if (! $request->session()->has('otp_user')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp', [
            'destination' => $this->mask($request->session()->get('otp_destination', '')),
            'debug' => $request->session()->get('otp_debug'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $destination = $request->session()->get('otp_destination');

        $user = $this->otp->verify($destination, $data['code'], 'login');
        if (! $user) {
            throw ValidationException::withMessages(['code' => t('errors.otpInvalid')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(['otp_user', 'otp_destination', 'otp_debug']);
        $user->forceFill(['last_login_at' => now(), 'phone_verified_at' => now()])->saveQuietly();

        return $this->home();
    }

    public function forgot()
    {
        return view('auth.forgot');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function home()
    {
        return redirect()->intended(
            Auth::user()->isStaff() ? route('admin.dashboard') : route('member.home')
        );
    }

    private function mask(string $phone): string
    {
        return preg_replace('/(\d{3})\d+(\d{3})$/', '$1••••$2', $phone) ?: $phone;
    }
}
