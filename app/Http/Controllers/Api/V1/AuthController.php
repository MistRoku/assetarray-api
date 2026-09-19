<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * Endpoints for login, logout, token refresh, password reset, and profile management.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Login
     *
     * Authenticate using email and password, then return a Sanctum token.
     * Failures are deliberately generic (see AuthService).
     *
     * @bodyParam email string required The user email. Example: manager@assetarray.test
     * @bodyParam password string required The user password. Example: password
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
            ],
        ]);
    }

    /**
     * Logout
     *
     * Revokes only the token used for this request — other devices stay signed in.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * Refresh token
     *
     * Deletes the current token and issues a new one. The client must
     * discard the old token — it stops working immediately.
     */
    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refreshToken($request->user());

        return response()->json([
            'message' => 'Token refreshed.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Forgot password
     *
     * Sends a password reset link via the configured broker.
     *
     * @bodyParam email string required The user email. Example: manager@assetarray.test
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->sendPasswordResetLink($request->validated());

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Reset password
     *
     * Consumes the reset token and sets the new password.
     *
     * @bodyParam token string required Reset token.
     * @bodyParam email string required User email.
     * @bodyParam password string required New password.
     * @bodyParam password_confirmation string required Must match password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->resetPassword($request->validated());

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Get current user profile
     *
     * Includes the assigned branch (when the user has one).
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('branch:id,name,code')),
        ]);
    }

    /**
     * Update current user profile
     *
     * Partial updates allowed. NOTE: the password is assigned plain-text on
     * purpose — the User model's 'hashed' cast hashes it once. Calling
     * Hash::make() here would double-hash and lock the user out.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update($data);

        // refresh() (not fresh()): reloads in place and stays non-nullable.
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->load('branch:id,name,code')),
        ]);
    }
}
