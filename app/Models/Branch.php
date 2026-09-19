<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical store/warehouse location.
 *
 * Branches own stock levels, transfers, purchase orders and stock takes.
 * Soft-deleted so history (movements, audit logs) survives deactivation.
 * Deactivation also flips is_active=false — see BranchService::deactivate().
 *
 * @mixin IdeHelperBranch
 */
class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'tax_rate',
        'operating_hours',
        'is_active',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'operating_hours' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Users assigned to this branch.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Per-product quantities held at this branch.
     *
     * @return HasMany<StockLevel, $this>
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Outgoing transfers (this branch is the source).
     *
     * @return HasMany<StockTransfer, $this>
     */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    /**
     * Incoming transfers (this branch is the destination).
     *
     * @return HasMany<StockTransfer, $this>
     */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    /**
     * Purchase orders raised for this branch.
     *
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Stock takes performed at this branch.
     *
     * @return HasMany<StockTake, $this>
     */
    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class);
    }

    /**
     * Scope to only active branches.
     *
     * @param  Builder<Branch>  $query  @return Builder<Branch>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
