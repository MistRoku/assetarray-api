<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Current on-hand quantity of one product at one branch.
 *
 * Unique per (product_id, branch_id). All writes go through services with
 * lockForUpdate() — never update quantity directly, to avoid lost updates.
 */
class StockLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Product this level tracks.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Branch holding the stock.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
