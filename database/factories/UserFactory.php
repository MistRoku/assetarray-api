<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     *
     * Cached so hundreds of factory users don't re-run bcrypt per row.
     * Hash::make() here does NOT double-hash: the User model's 'hashed'
     * cast skips values Hash::isHashed() already recognises.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Defaults to an active staff member with no branch — the least
     * privileged usable user. Elevate explicitly via states below.
     * ($model is intentionally unset: HasFactory resolves
     * Database\Factories\UserFactory ↔ App\Models\User by convention.)
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'branch_id' => null,
            'is_active' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Full-access user. Bypasses every policy via before() hooks.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * Branch manager. Pair with a branch_id (or assign after creation) —
     * most manager gates compare against the user's own branch.
     */
    public function branchManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_BRANCH_MANAGER,
        ]);
    }

    /**
     * Deactivated user. Login rejects these (AuthService) and the active
     * middleware 403s their existing tokens.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
