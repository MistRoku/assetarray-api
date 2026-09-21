<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end smoke coverage for the v1 API: auth lifecycle, role gates,
 * and the branch/product read paths with their resource shapes.
 *
 * Runs on in-memory sqlite (see phpunit.xml) — never touches dev data.
 */
class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $manager;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Mgr',
            'email' => 'mgr@example.com',
            'password' => 'password123',
            'role' => User::ROLE_BRANCH_MANAGER,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'name' => 'Stf',
            'email' => 'stf@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STAFF,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    public function test_login_returns_token_and_profile_uses_resource_shape(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'mgr@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonStructure(['data' => ['user' => ['id', 'email', 'role'], 'token', 'token_type']]);

        $me = $this->getJson('/api/v1/auth/profile', [
            'Authorization' => 'Bearer '.$response->json('data.token'),
        ]);

        $me->assertOk()->assertJsonPath('data.email', 'mgr@example.com');
    }

    public function test_inactive_user_is_blocked_by_middleware(): void
    {
        $this->manager->update(['is_active' => false]);
        $token = $this->manager->createToken('smoke')->plainTextToken;

        $this->getJson('/api/v1/auth/profile', ['Authorization' => "Bearer {$token}"])
            ->assertForbidden()
            ->assertJsonPath('message', 'Account is inactive.');
    }

    public function test_branch_create_is_super_admin_only(): void
    {
        // Manager is stopped by BranchPolicy (super-admin only writes).
        $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/branches', ['name' => 'X', 'code' => 'X'])
            ->assertForbidden();

        // But listing is open to any authenticated user.
        $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_product_crud_and_price_history_gate(): void
    {
        $category = Category::create(['name' => 'G', 'slug' => 'g', 'is_active' => true]);

        $created = $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/products', [
            'name' => 'Widget',
            'category_id' => $category->id,
            'cost_price' => 10,
            'selling_price' => 15,
        ]);

        $created->assertCreated()->assertJsonPath('data.sku', fn ($sku) => str_starts_with($sku, 'PRD-'));

        $productId = $created->json('data.id');

        // Staff cannot delete (policy) and cannot see price history (cost data).
        $this->actingAs($this->staff, 'sanctum')
            ->deleteJson("/api/v1/products/{$productId}")
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/v1/products/{$productId}/price-history")
            ->assertForbidden();

        // Manager updates the price → observer journals it → history visible.
        $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/v1/products/{$productId}", ['selling_price' => 20])
            ->assertOk();

        $this->actingAs($this->manager, 'sanctum')
            ->getJson("/api/v1/products/{$productId}/price-history")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_login_is_rate_limited(): void
    {
        // Login allows 5 attempts per minute (see routes/api.php); the 6th
        // must 429 even with valid credentials — brute-force protection on
        // the public endpoint.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'mgr@example.com',
                'password' => 'wrong',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mgr@example.com',
            'password' => 'password123',
        ])->assertStatus(429);
    }

    public function test_reports_require_manager_and_audit_requires_super_admin(): void
    {
        $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/v1/reports/low-stock')
            ->assertForbidden();

        $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/v1/reports/low-stock')
            ->assertOk();

        $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();
    }
}
