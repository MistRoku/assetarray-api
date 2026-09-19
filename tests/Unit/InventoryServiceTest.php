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
}
