<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Role gates used by controllers via $this->authorize('...'). These are
     * intentionally role-based (not policies) because they guard cross-model
     * concerns — reports and the audit trail — rather than one entity.
     */
    public function boot(): void
    {
        // Everything money- or margin-adjacent (reports, exports).
        Gate::define('manager-or-above', fn (User $user): bool => $user->isManagerOrAbove());

        // The audit trail contains before/after snapshots — super-admins only.
        Gate::define('super-admin', fn (User $user): bool => $user->isSuperAdmin());
    }
}
