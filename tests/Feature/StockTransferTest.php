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
 * Full transfer lifecycle through the HTTP layer: request → approve
 * (source decrements) → receive (destination increments, level created).
 * Runs as super-admin so policy gates don't mask service behaviour —
 * policy coverage lives in ApiSmokeTest.
 */
class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_transfer_workflow(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $fromBranch = Branch::factory()->create();
        $toBranch = Branch::factory()->create();
        $product = Product::factory()->create();

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $fromBranch->id,
            'quantity' => 20,
        ]);

        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/v1/inventory/transfers', [
            'product_id' => $product->id,
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 5,
        ]);

        $create->assertCreated();

        $transferId = $create->json('data.id');

        $this->putJson("/api/v1/inventory/transfers/{$transferId}/approve")
            ->assertOk();

        $this->putJson("/api/v1/inventory/transfers/{$transferId}/receive")
            ->assertOk();

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $fromBranch->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $toBranch->id,
            'quantity' => 5,
        ]);
    }
}
