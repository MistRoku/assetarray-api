<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auth lifecycle: login shape, inactive rejection, and the auth guard.
 * (Rate limiting has its own test in ApiSmokeTest.)
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'manager@assetarray.test',
            'role' => User::ROLE_BRANCH_MANAGER,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'manager@assetarray.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ]);
    }

    /**
     * Inactive accounts fail closed at login with a field error (422, not
     * 401) — same shape as bad credentials, so callers handle one path.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@assetarray.test',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@assetarray.test',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/profile')
            ->assertUnauthorized();
    }
}
