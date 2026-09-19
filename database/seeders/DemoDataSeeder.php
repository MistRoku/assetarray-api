<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic demo dataset for local development and showcases.
 *
 * Fixed credentials (all password "password"):
 * admin@assetarray.test (super_admin), manager@assetarray.test and
 * east.manager@... (branch managers), staff@assetarray.test (staff).
 *
 * Everything is updateOrCreate() by unique key, so reseeding is safe and
 * converges instead of duplicating. The East branch holds 4 units against
 * a threshold of 10 on purpose — it demos the low-stock flow out of the box.
 *
 * NEVER in production: known passwords plus junk rows. run() refuses
 * production unless explicitly forced (see below).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && ! (bool) env('ALLOW_DEMO_SEED', false)) {
            $this->command?->warn('DemoDataSeeder skipped: refusing to seed demo data with known passwords in production. Set ALLOW_DEMO_SEED=true to override.');

            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@assetarray.test'],
            [
                'name' => 'Super Admin',
                // Hash::make() is safe with the model's 'hashed' cast: already-
                // hashed values pass through via Hash::isHashed(), no double hash.
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'address' => '123 Commerce Street',
                'phone' => '+1234567890',
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
            ]
        );

        $eastBranch = Branch::updateOrCreate(
            ['code' => 'EAST'],
            [
                'name' => 'East Branch',
                'address' => '456 Distribution Ave',
                'phone' => '+1234567891',
                'tax_rate' => 15.00,
                'operating_hours' => [
                    'monday' => ['08:00', '17:00'],
                    'tuesday' => ['08:00', '17:00'],
                    'wednesday' => ['08:00', '17:00'],
                    'thursday' => ['08:00', '17:00'],
                    'friday' => ['08:00', '17:00'],
                    'saturday' => [],
                    'sunday' => [],
                ],
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@assetarray.test'],
            [
                'name' => 'Branch Manager',
                'password' => Hash::make('password'),
                'role' => User::ROLE_BRANCH_MANAGER,
                'branch_id' => $mainBranch->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'east.manager@assetarray.test'],
            [
                'name' => 'East Branch Manager',
                'password' => Hash::make('password'),
                'role' => User::ROLE_BRANCH_MANAGER,
                'branch_id' => $eastBranch->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@assetarray.test'],
            [
                'name' => 'Warehouse Staff',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'branch_id' => $mainBranch->id,
                'is_active' => true,
            ]
        );

        $electronics = Category::updateOrCreate(
            ['slug' => 'electronics'],
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories.',
                'is_active' => true,
            ]
        );

        $supplier = Supplier::updateOrCreate(
            ['email' => 'sales@supplier.test'],
            [
                'name' => 'Global Supplier Co',
                'contact_person' => 'Jane Buyer',
                'phone' => '+9876543210',
                'address' => 'Supplier Road 1',
                'is_active' => true,
            ]
        );

        $product = Product::updateOrCreate(
            ['sku' => 'PRD-DEMO0001'],
            [
                'name' => 'Demo Wireless Mouse',
                'description' => 'A demo product for showcase purposes.',
                'category_id' => $electronics->id,
                'supplier_id' => $supplier->id,
                'cost_price' => 12.50,
                'selling_price' => 29.99,
                'min_stock_threshold' => 10,
                'is_active' => true,
            ]
        );

        StockLevel::updateOrCreate(
            [
                'product_id' => $product->id,
                'branch_id' => $mainBranch->id,
            ],
            [
                'quantity' => 45,
            ]
        );

        // Deliberately below the threshold of 10: showcases low-stock
        // reports, alerts and the CheckLowStockJob flow without setup.
        StockLevel::updateOrCreate(
            [
                'product_id' => $product->id,
                'branch_id' => $eastBranch->id,
            ],
            [
                'quantity' => 4,
            ]
        );
    }
}
