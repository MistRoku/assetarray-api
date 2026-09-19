<?php

namespace App\Models;

use App\Support\TransferCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Branch-to-branch stock movement request.
 *
 * Lifecycle: pending → approved → received (or rejected). Source stock is
 * decremented at approval, destination incremented at receipt — never both
 * at once, so in-flight quantity is never double-counted. See TransferService.
 */
class StockTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'transfer_code',
        'from_branch_id',
        'to_branch_id',
        'product_id',
        'quantity',
        'status',
        'requested_by',
        'approved_by',
        'rejected_reason',
        'transferred_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'transferred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer): void {
            if (blank($transfer->transfer_code)) {
                $transfer->transfer_code = TransferCodeGenerator::make();
            }
        });
    }

    /**
     * Branch losing the stock.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * Branch gaining the stock.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * Product being moved.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who requested the transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * User who approved it (null until approved).
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
