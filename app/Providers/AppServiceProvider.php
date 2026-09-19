<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AuditLogObserver;
use App\Observers\ProductObserver;
use App\Observers\StockMovementObserver;
use App\Policies\BranchPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\StockTakePolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Central authorization + observer wiring.
 *
 * Conventions used here (pick ONE registration style per concern — doubling
 * them double-fires handlers):
 * - Gates: defined explicitly; they guard cross-model concerns (reports,
 *   audit trail) that belong to no single policy.
 * - Policies: registered explicitly via Gate::policy(). Laravel would also
 *   auto-discover them by naming convention; explicit wins for greppability.
 * - Observers: registered explicitly via Model::observe(). The models must
 *   NOT also carry #[ObservedBy] for the same observer — both mechanisms
 *   register on the dispatcher, so ProductObserver::updating would journal
 *   every price change TWICE.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The audit trail contains before/after snapshots — super-admins only.
        Gate::define('super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Everything money- or margin-adjacent (reports, exports).
        Gate::define('manager-or-above', function (User $user) {
            return $user->isManagerOrAbove();
        });

        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(StockTransfer::class, StockTransferPolicy::class);
        Gate::policy(StockTake::class, StockTakePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);

        Product::observe(ProductObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        AuditLog::observe(AuditLogObserver::class);
    }
}
