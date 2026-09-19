<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root seeder: demo dataset only (see DemoDataSeeder).
 *
 * NOTE: no WithoutModelEvents trait on purpose. That trait also silences
 * nested $this->call() seeders, which would disable model hooks the app
 * depends on — e.g. Product::booted() SKU generation. Reseeds stay clean
 * anyway: updateOrCreate() with identical attributes fires no model events.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
