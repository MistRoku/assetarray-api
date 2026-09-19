<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<StockLevel, $this> */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /** @return HasMany<StockTransfer, $this> */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    /** @return HasMany<StockTransfer, $this> */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** @return HasMany<StockTake, $this> */
    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class);
    }

    /** @param Builder<Branch> $query @return Builder<Branch> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
