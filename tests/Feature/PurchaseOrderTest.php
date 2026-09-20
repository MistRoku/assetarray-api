<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Purchase order lifecycle through HTTP: draft → sent → received, with
 * branch stock bumped and a receipt movement written on arrival.
 */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_purchase_order_lifecycle(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($manager);

        $created = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_ordered' => 10, 'unit_cost' => 5.00],
            ],
        ]);

        $created->assertCreated()->assertJsonPath('data.status', 'draft');

        $poId = $created->json('data.id');
        $itemId = $created->json('data.items.0.id');

        $this->putJson("/api/v1/purchase-orders/{$poId}/send")->assertOk();

        // Partial receipt first: header must show partially_received.
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [
            'items' => [
                ['purchase_order_item_id' => $itemId, 'quantity_received' => 4],
            ],
        ])->assertOk()->assertJsonPath('data.status', 'partially_received');

        // Remainder: fully received, stock bumped, movement written.
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [
            'items' => [
                ['purchase_order_item_id' => $itemId, 'quantity_received' => 6],
            ],
        ])->assertOk()->assertJsonPath('data.status', 'received');

        $this->assertDatabaseHas('stock_levels', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'movement_type' => 'receipt',
        ]);
    }

    /**
     * Over-receiving beyond the ordered remainder must 422, not clamp.
     */
    public function test_receive_rejects_quantity_above_remaining(): void
    {
        $manager = User::factory()->branchManager()->create();
        $branch = Branch::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($manager);

        $created = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_ordered' => 2, 'unit_cost' => 5.00],
            ],
        ])->assertCreated();

        $poId = $created->json('data.id');
        $itemId = $created->json('data.items.0.id');

        $this->putJson("/api/v1/purchase-orders/{$poId}/send")->assertOk();

        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [
            'items' => [
                ['purchase_order_item_id' => $itemId, 'quantity_received' => 3],
            ],
        ])->assertStatus(422);
    }
}
