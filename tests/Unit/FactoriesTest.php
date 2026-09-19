<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Factory regression net: bulk-creates every factory to catch faker
 * collisions against unique columns (category name/slug, branch code,
 * supplier email, product SKU) before they flake real tests.
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_factories_create_valid_rows_in_bulk(): void
    {
        $branches = Branch::factory()->count(10)->create();
        $categories = Category::factory()->count(20)->create();
        $suppliers = Supplier::factory()->count(10)->create();

        $products = Product::factory()->count(30)->create();

        $this->assertCount(10, $branches);
        $this->assertCount(20, $categories);
        $this->assertCount(10, $suppliers);
        $this->assertCount(30, $products);

        // Every product got a collision-free SKU from the generator (the
        // factory omits sku on purpose) and a positive margin.
        $this->assertTrue($products->pluck('sku')->unique()->count() === 30);
        $this->assertTrue($products->every(fn (Product $p) => $p->selling_price >= $p->cost_price));
    }

    public function test_user_states_and_login_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $manager = User::factory()->branchManager()->create();
        $inactive = User::factory()->inactive()->create();

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($manager->isBranchManager());
        $this->assertFalse($inactive->is_active);

        // The factory password must verify (guards against double-hashing
        // between Hash::make() and the model's 'hashed' cast).
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
