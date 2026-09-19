<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Inventory adjustment endpoint: happy path writes both the level and the
 * ledger row, and the negative-stock guard requires a correction reason.
 */
class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_adjust_stock(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/inventory/adjust', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => -2,
            'reason' => 'Damaged unit',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 8,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'movement_type' => 'adjustment',
            'quantity' => -2,
        ]);
    }

    /**
     * 1 − 5 goes negative and 'Damage' doesn't contain 'correction', so the
     * service must reject with a field error (not silently clamp to zero).
     */
    public function test_adjustment_cannot_create_negative_stock_without_correction_reason(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/inventory/adjust', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => -5,
            'reason' => 'Damage',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }
}
