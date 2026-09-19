<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * No 'sku' key on purpose: Product::booted() fills it via the
     * collision-checked SkuGenerator (unique incl. trashed rows), which a
     * faker random string can't guarantee. Selling price derives from cost
     * so factory rows always have a sane positive margin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 1, 100);

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'cost_price' => $cost,
            'selling_price' => round($cost * fake()->randomFloat(2, 1.2, 2.5), 2),
            'tax_rate_override' => null,
            // ean13 isn't faker-unique, but the column is a plain (non-unique)
            // index, so repeats are legal. Validation-level uniqueness is a
            // request concern, not a factory one.
            'barcode' => fake()->ean13(),
            'image_url' => null,
            'min_stock_threshold' => 5,
            'is_active' => true,
        ];
    }
}
