<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sanctum token authentication plus the password-reset flow.
 *
 * Login is deliberately generic on failure ("credentials are incorrect")
 * so attackers can't probe which emails exist. Inactive accounts are
 * rejected after the password check for the same reason.
 */
final class AuthService
{
    /**
     * Verify credentials, stamp last_login_at and issue a Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string, token_type: string}
     *
     * @throws ValidationException On bad credentials or inactive account.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

        $token = $user->createToken('assetarray-api')->plainTextToken;

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke only the token used for this request — other devices stay signed in.
     * Nullsafe: no-op when the request carries no token (e.g. already revoked).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Rotate tokens: revoke the current one and issue a fresh token.
     * Callers must return the new string — the old one stops working immediately.
     */
    public function refreshToken(User $user): string
    {
        $user->currentAccessToken()?->delete();

        return $user->createToken('assetarray-api')->plainTextToken;
    }

    /**
     * Queue the password-reset email via the configured broker.
     *
     * @param  array{email: string}  $credentials
     *
     * @throws ValidationException When the broker reports anything but RESET_LINK_SENT.
     */
    public function sendPasswordResetLink(array $credentials): string
    {
        $status = Password::sendResetLink($credentials);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Consume a reset token and set the new password.
     *
     * The password is assigned plain-text on purpose: the User model's
     * 'hashed' cast hashes it once. Hashing here would double-hash and
     * lock the user out.
     *
     * @param  array{email: string, token: string, password: string}  $credentials
     *
     * @throws ValidationException On expired/invalid token.
     */
    public function resetPassword(array $credentials): string
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                // NOTE: User casts password => 'hashed', so assign plain text —
                // Hash::make() here would double-hash.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}
