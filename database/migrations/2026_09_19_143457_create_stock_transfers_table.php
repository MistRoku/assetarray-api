<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code', 20)->unique();

            $table->foreignId('from_branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('to_branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->integer('quantity');

            $table->enum('status', [
                'pending',
                'approved',
                'in_transit',
                'received',
                'rejected',
            ])->default('pending');

            $table->foreignId('requested_by')
                ->constrained('users');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users');

            $table->string('rejected_reason')->nullable();
            $table->timestamp('transferred_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
