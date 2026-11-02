<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->home();
        }

        $request->session()->forget(['otp_user', 'otp_destination', 'otp_debug', 'otp_expires_at', 'otp_remember']);

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $key = 'web-login:'.hash('sha256', Str::lower($data['login']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => t('auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);
        $user = (new Timebox)->call(
            fn (): ?User => authenticate_login($data['login'], $data['password']),
            200_000,
        );

        if (! $user || $user->status !== 'active') {
            throw ValidationException::withMessages(['login' => t('auth.badCredentials')]);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget(['otp_user', 'otp_destination', 'otp_debug', 'otp_expires_at', 'otp_remember']);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $this->home();
    }

    public function forgot(): View
    {
        return view('auth.forgot');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function home(): RedirectResponse
    {
        return redirect()->intended(
            Auth::user()->isStaff() ? route('admin.dashboard') : route('member.home')
        );
    }
}
