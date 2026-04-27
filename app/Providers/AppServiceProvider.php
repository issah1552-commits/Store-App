<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InternalMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\InternalMovementPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\TransferPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AuditLogService::class);
        $this->app->singleton(\App\Services\AppNavigationService::class);
        $this->app->singleton(\App\Services\Reports\DashboardService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Transfer::class, TransferPolicy::class);
        Gate::policy(InternalMovement::class, InternalMovementPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(fn (User $user, string $ability) => $user->isAdmin() ? true : null);

        if (app()->environment('testing')) {
            config(['view.compiled' => sys_get_temp_dir()]);
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
