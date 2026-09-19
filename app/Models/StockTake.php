<?php

namespace App\Models;

use App\Support\StockTakeReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Physical inventory count session for a branch.
 *
 * Lifecycle: open → submitted → approved (or cancelled). Approval writes the
 * counted quantities back to stock levels and emits adjustment movements.
 * See StockTakeService.
 *
 * @mixin IdeHelperStockTake
 */
class StockTake extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference',
        'branch_id',
        'created_by',
        'status',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockTake $take): void {
            if (blank($take->reference)) {
                $take->reference = StockTakeReferenceGenerator::make();
            }
        });
    }

    /**
     * Branch being counted.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * User who started the count.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Per-product count lines with system vs counted quantities.
     *
     * @return HasMany<StockTakeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTakeItem::class);
    }
}
