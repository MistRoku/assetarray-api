<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * Phone is truncated to the 30-char column — faker numbers with
     * extensions overflow it on MySQL (sqlite ignores varchar limits, which
     * hides the bug until production seeding).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => Str::limit(fake()->phoneNumber(), 30, ''),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
