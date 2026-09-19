<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Immutable audit trail row for entity creates/updates/deletes.
 *
 * Timestamps are managed manually (single created_at) and updates/deletes
 * throw — history can only ever be appended. Write via AuditLogService.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAuditLog {}
}

namespace App\Models{
/**
 * A physical store/warehouse location.
 *
 * Branches own stock levels, transfers, purchase orders and stock takes.
 * Soft-deleted so history (movements, audit logs) survives deactivation.
 * Deactivation also flips is_active=false — see BranchService::deactivate().
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockLevel> $stockLevels
 * @property-read int|null $stock_levels_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTake> $stockTakes
 * @property-read int|null $stock_takes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTransfer> $transfersFrom
 * @property-read int|null $transfers_from_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTransfer> $transfersTo
 * @property-read int|null $transfers_to_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBranch {}
}

namespace App\Models{
/**
 * Product grouping. Name and slug are globally unique.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCategory {}
}

namespace App\Models{
/**
 * In-app notification for a single user.
 *
 * Unread = read_at null. Bulk reads use a direct update (see
 * NotificationService::markAllRead) to avoid N model events.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperNotification {}
}

namespace App\Models{
/**
 * A sellable inventory item.
 *
 * The SKU is auto-generated on create (see booted()) when left blank, and
 * is globally unique including soft-deleted rows so SKUs are never recycled.
 * Prices use decimal:2 casts — never floats — to avoid rounding drift.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPriceHistory> $priceHistories
 * @property-read int|null $price_histories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderItem> $purchaseOrderItems
 * @property-read int|null $purchase_order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockLevel> $stockLevels
 * @property-read int|null $stock_levels_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product search(?string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProduct {}
}

namespace App\Models{
/**
 * Append-only record of a product price change.
 *
 * Uses changed_at (not created_at/updated_at) as the event timestamp.
 *
 * @property int $id
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\User|null $changedBy
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPriceHistory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProductPriceHistory {}
}

namespace App\Models{
/**
 * Supplier restock order for a branch.
 *
 * Lifecycle: draft → sent → partially_received → received (or cancelled).
 * Receiving is incremental per line item — see PurchaseOrderService.
 * The po_number is auto-generated on create when left blank.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $orderedBy
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPurchaseOrder {}
}

namespace App\Models{
/**
 * One ordered product line within a purchase order.
 *
 * quantity_received grows incrementally via receiveGoods() until it equals
 * quantity_ordered; over-receiving is rejected per line.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPurchaseOrderItem {}
}

namespace App\Models{
/**
 * Current on-hand quantity of one product at one branch.
 *
 * Unique per (product_id, branch_id). All writes go through services with
 * lockForUpdate() — never update quantity directly, to avoid lost updates.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockLevel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockLevel {}
}

namespace App\Models{
/**
 * Append-only ledger entry for a single quantity change.
 *
 * Signed quantity: positive for inbound (receipt, transfer_in), negative for
 * outbound (sale, transfer_out). reference_type/reference_id is a polymorphic
 * pointer to the source (e.g. StockTransfer, PurchaseOrderItem, StockLevel).
 * Rows are never edited — corrections are new adjustment rows.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockMovement {}
}

namespace App\Models{
/**
 * Physical inventory count session for a branch.
 *
 * Lifecycle: open → submitted → approved (or cancelled). Approval writes the
 * counted quantities back to stock levels and emits adjustment movements.
 * See StockTakeService.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTakeItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockTake {}
}

namespace App\Models{
/**
 * One counted product line within a stock take.
 *
 * variance = counted_quantity - system_quantity (computed at submit time).
 * Unique per (stock_take_id, product_id).
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\StockTake|null $stockTake
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockTakeItem {}
}

namespace App\Models{
/**
 * Branch-to-branch stock movement request.
 *
 * Lifecycle: pending → approved → received (or rejected). Source stock is
 * decremented at approval, destination incremented at receipt — never both
 * at once, so in-flight quantity is never double-counted. See TransferService.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\Branch|null $fromBranch
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\User|null $requestedBy
 * @property-read \App\Models\Branch|null $toBranch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockTransfer {}
}

namespace App\Models{
/**
 * Goods supplier. Soft-deleted so purchase-order history keeps its source.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSupplier {}
}

namespace App\Models{
/**
 * Application user authenticated via Sanctum tokens.
 *
 * Roles (super_admin > branch_manager > staff) drive policies and the
 * isManagerOrAbove() gate used across inventory requests. The password
 * 'hashed' cast means services must assign plain text — never Hash::make().
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Notification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

