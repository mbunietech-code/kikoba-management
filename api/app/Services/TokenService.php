<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Issue an access token (Sanctum) + a long-lived refresh token.
     *
     * @return array{access_token:string, refresh_token:string, token_type:string, expires_in:int}
     */
    public function issue(User $user, ?Request $request = null): array
    {
        $accessTtl = config('services.auth.access_ttl');
        $refreshTtl = config('services.auth.refresh_ttl');

        // one live access token per login session — clear stale ones
        $user->tokens()->where('name', 'access')->where('created_at', '<', now()->subDay())->delete();

        $access = $user->createToken(
            name: 'access',
            abilities: ['*'],
            expiresAt: now()->addSeconds($accessTtl),
        );

        $plainRefresh = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefresh),
            'device' => $request?->userAgent(),
            'ip_address' => $request?->ip(),
            'expires_at' => now()->addSeconds($refreshTtl),
        ]);

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $plainRefresh,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }

    /**
     * Exchange a still-valid refresh token for a fresh access token.
     * The refresh token itself is reused (no rotation) to keep concurrent
     * refresh attempts idempotent for SPA clients.
     */
    public function refresh(string $plainRefresh, ?Request $request = null): ?array
    {
        $record = RefreshToken::with('user')
            ->where('token_hash', hash('sha256', $plainRefresh))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record || ! $record->user) {
            return null;
        }

        $user = $record->user;
        $accessTtl = config('services.auth.access_ttl');

        // drop expired access tokens, keep the refresh token as-is
        $user->tokens()->where('name', 'access')->where('expires_at', '<', now())->delete();

        $access = $user->createToken('access', ['*'], now()->addSeconds($accessTtl));

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $plainRefresh,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
        $user->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
