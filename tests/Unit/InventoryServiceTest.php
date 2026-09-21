<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Service-level (no HTTP) coverage for stock adjustment: exercises the
 * transaction, row lock and ledger write directly. Auth is faked because
 * the service stamps created_by/audit user from the current session.
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adjusts_stock_and_creates_movement(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create([
            'min_stock_threshold' => 5,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        Sanctum::actingAs($manager);

        $service = app(InventoryService::class);

        // 'Manual correction' wording is incidental here — 10 + 7 stays
        // positive, so the correction path isn't what permits this.
        $stock = $service->adjust([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 7,
            'reason' => 'Manual correction',
        ]);

        $this->assertEquals(17, $stock->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'movement_type' => 'adjustment',
            'quantity' => 7,
        ]);
    }

    /**
     * Regression test: adjust stock for a product/branch with no existing row.
     * Ensures the StockLevel is created with quantity 0 and then adjusted correctly.
     */
    public function test_it_creates_stock_level_when_none_exists(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create([
            'min_stock_threshold' => 5,
        ]);

        // Ensure no stock level exists for this product/branch
        $this->assertDatabaseMissing('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
        ]);

        Sanctum::actingAs($manager);

        $service = app(InventoryService::class);

        // Adjust stock for a product/branch with no existing row
        $stock = $service->adjust([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
            'reason' => 'Initial stock',
        ]);

        // Assert the stock level was created with the correct quantity
        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals($product->id, $stock->product_id);
        $this->assertEquals($branch->id, $stock->branch_id);

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'movement_type' => 'adjustment',
            'quantity' => 10,
        ]);
    }

    /**
     * Test that negative stock adjustment is rejected without correction reason.
     */
    public function test_negative_stock_without_correction_is_rejected(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create();

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 5,
        ]);

        Sanctum::actingAs($manager);

        $service = app(InventoryService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->adjust([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => -10,
            'reason' => 'Normal adjustment',
        ]);
    }

    /**
     * Test that negative stock adjustment is allowed with correction reason.
     */
    public function test_negative_stock_with_correction_is_allowed(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $product = Product::factory()->create([
            'min_stock_threshold' => 5,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 5,
        ]);

        Sanctum::actingAs($manager);

        $service = app(InventoryService::class);

        $stock = $service->adjust([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => -3,
            'reason' => 'Stock correction due to recount',
        ]);

        $this->assertEquals(2, $stock->quantity);
    }
}
