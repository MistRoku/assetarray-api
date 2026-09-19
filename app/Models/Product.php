<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Support\SkuGenerator;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A sellable inventory item.
 *
 * The SKU is auto-generated on create (see booted()) when left blank, and
 * is globally unique including soft-deleted rows so SKUs are never recycled.
 * Prices use decimal:2 casts — never floats — to avoid rounding drift.
 * Price changes are journaled by ProductObserver.
 *
 * @mixin IdeHelperProduct
 */
#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'supplier_id',
        'cost_price',
        'selling_price',
        'tax_rate_override',
        'barcode',
        'image_url',
        'min_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate_override' => 'decimal:2',
        'min_stock_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Auto-fill the SKU so callers never have to invent one.
     * Explicitly provided SKUs are left untouched.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->sku)) {
                $product->sku = SkuGenerator::make();
            }
        });
    }

    /**
     * Owning category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Preferred supplier (nullable).
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Per-branch quantities for this product.
     *
     * @return HasMany<StockLevel, $this>
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Ledger of every quantity change (receipts, sales, adjustments, transfers).
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Historical cost/selling price changes.
     *
     * @return HasMany<ProductPriceHistory, $this>
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /**
     * Purchase-order line items referencing this product.
     *
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Free-text search across name and SKU.
     * Blank term returns the query untouched so the filter is optional.
     *
     * @param  Builder<Product>  $query  @return Builder<Product>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to sellable (active) products.
     *
     * @param  Builder<Product>  $query  @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
