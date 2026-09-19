<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Product endpoints: creation shape/SKU generation and the create-policy
 * gate (managers yes, staff no).
 */
class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_manager_can_create_product(): void
    {
        $manager = User::factory()->branchManager()->create();
        $category = Category::factory()->create();

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Widget',
            'category_id' => $category->id,
            'cost_price' => 10.50,
            'selling_price' => 19.99,
            'min_stock_threshold' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Widget')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'cost_price',
                    'selling_price',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Widget',
        ]);
    }

    /**
     * StoreProductRequest::authorize() denies staff → 403 before any
     * validation or service work happens.
     */
    public function test_staff_cannot_create_product(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $category = Category::factory()->create();

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Unauthorized Widget',
            'category_id' => $category->id,
            'cost_price' => 10,
            'selling_price' => 20,
        ]);

        $response->assertForbidden();
    }
}
