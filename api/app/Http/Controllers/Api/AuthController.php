<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TokenService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly OtpService $otp,
    ) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $org = Organization::query()->firstOrCreate(
            ['code' => 'BK'],
            ['name' => 'Benja Kikoba', 'currency' => 'TZS', 'status' => 'active'],
        );

        $user = User::create([
            'organization_id' => $org->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
        ]);
        $user->assignRole('member');

        return ApiResponse::created([
            'user' => new UserResource($user->load(['roles', 'member'])),
            ...$this->tokens->issue($user, $request),
        ], 'Registered');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'These credentials do not match our records.']);
        }
        if ($user->status !== 'active') {
            return ApiResponse::error('ACCOUNT_SUSPENDED', 'Your account is not active.', 403);
        }

        // Optional OTP step — enabled when SMS is configured and the user has a phone.
        if (config('services.beem.enabled') && $user->phone) {
            $otp = $this->otp->issue($user, 'login');

            return ApiResponse::ok([
                'otp_required' => true,
                'destination' => $this->maskPhone($user->phone),
                'debug_code' => $otp->getAttribute('plain_code'),
            ], 'OTP sent');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::ok([
            'user' => new UserResource($user->load(['roles', 'member'])),
            ...$this->tokens->issue($user, $request),
        ], 'Signed in');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'destination' => ['required', 'string'],
            'code' => ['required', 'string'],
            'purpose' => ['nullable', 'string'],
        ]);

        $user = $this->otp->verify($data['destination'], $data['code'], $data['purpose'] ?? 'login');

        if (! $user) {
            return ApiResponse::error('OTP_INVALID', 'The code is invalid or has expired.', 422);
        }

        $user->forceFill(['last_login_at' => now(), 'phone_verified_at' => now()])->save();

        return ApiResponse::ok([
            'user' => new UserResource($user->load(['roles', 'member'])),
            ...$this->tokens->issue($user, $request),
        ], 'Verified');
    }

    public function requestOtp(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string']]);
        $user = User::where('email', $data['login'])->orWhere('phone', $data['login'])->first();

        if ($user && $user->phone) {
            $otp = $this->otp->issue($user, 'login');

            return ApiResponse::ok([
                'destination' => $this->maskPhone($user->phone),
                'debug_code' => $otp->getAttribute('plain_code'),
            ], 'OTP sent');
        }

        // do not leak which accounts exist
        return ApiResponse::message('If the account exists, a code has been sent.');
    }

    public function refresh(Request $request)
    {
        $data = $request->validate(['refresh_token' => ['required', 'string']]);
        $pair = $this->tokens->refresh($data['refresh_token'], $request);

        if (! $pair) {
            return ApiResponse::error('REFRESH_INVALID', 'The refresh token is invalid or expired.', 401);
        }

        return ApiResponse::ok($pair, 'Token refreshed');
    }

    public function logout(Request $request)
    {
        $this->tokens->revokeAll($request->user());

        return ApiResponse::message('Signed out');
    }

    public function me(Request $request)
    {
        return ApiResponse::ok(
            new UserResource($request->user()->load(['roles', 'member', 'organization'])),
            'OK',
        );
    }

    private function maskPhone(string $phone): string
    {
        return preg_replace('/(\d{3})\d+(\d{3})$/', '$1••••$2', $phone);
    }
}
