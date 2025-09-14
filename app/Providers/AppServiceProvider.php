<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\PartialOrder;
use App\Models\PendingQuotation;
use App\Observers\OrderObserver;
use App\Observers\PartialOrderObserver;
use App\Observers\PendingQuotationObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\RolePermissionObserver;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Quotation;
use App\Observers\QuotationObserver;
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
     */
    public function boot(): void
    {
        Role::observe(RolePermissionObserver::class);
        Permission::observe(RolePermissionObserver::class);
        Quotation::observe(QuotationObserver::class);
        PendingQuotation::observe(PendingQuotationObserver::class);
        Order::observe(OrderObserver::class);
        PartialOrder::observe(PartialOrderObserver::class);
    }
}
