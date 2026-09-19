<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger entry for a single quantity change.
 *
 * Signed quantity: positive for inbound (receipt, transfer_in), negative for
 * outbound (sale, transfer_out). reference_type/reference_id is a polymorphic
 * pointer to the source (e.g. StockTransfer, PurchaseOrderItem, StockLevel).
 * Rows are never edited — corrections are new adjustment rows.
 * Enforced by StockMovementObserver.
 *
 * @mixin IdeHelperStockMovement
 */
class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_SALE = 'sale';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    protected $fillable = [
        'product_id',
        'branch_id',
        'movement_type',
        'quantity',
        'unit_price',
        'total_amount',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Movement this entry records stock for.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Branch where the movement happened.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * User who caused the movement (null for system imports).
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
