<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * Codes stay well under the 10-char DB limit (2 letters + 3 digits).
     * Phone is truncated to 20 chars — faker numbers with extensions would
     * overflow the column on MySQL (sqlite silently ignores the limit, which
     * hides the bug until production seeding).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Branch',
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'address' => fake()->address(),
            'phone' => Str::limit(fake()->phoneNumber(), 20, ''),
            'tax_rate' => 15.00,
            'operating_hours' => [
                'monday' => ['08:00', '17:00'],
                'tuesday' => ['08:00', '17:00'],
                'wednesday' => ['08:00', '17:00'],
                'thursday' => ['08:00', '17:00'],
                'friday' => ['08:00', '17:00'],
                'saturday' => ['09:00', '13:00'],
                'sunday' => [],
            ],
            'is_active' => true,
        ];
    }
}
