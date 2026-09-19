<?php

namespace App\Models;

use App\Support\PurchaseOrderNumberGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier restock order for a branch.
 *
 * Lifecycle: draft → sent → partially_received → received (or cancelled).
 * Receiving is incremental per line item — see PurchaseOrderService.
 * The po_number is auto-generated on create when left blank.
 *
 * @mixin IdeHelperPurchaseOrder
 */
class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'branch_id',
        'status',
        'total_amount',
        'notes',
        'ordered_by',
        'ordered_at',
        'received_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order): void {
            if (blank($order->po_number)) {
                $order->po_number = PurchaseOrderNumberGenerator::make();
            }
        });
    }

    /**
     * Supplier fulfilling the order.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Branch receiving the goods.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * User who raised the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    /**
     * Line items; each tracks ordered vs received quantities.
     *
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
