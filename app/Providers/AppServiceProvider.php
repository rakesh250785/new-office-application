<?php

namespace App\Providers;

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

        Quotation::observe(classes: QuotationObserver::class);
        // repeat for Order, PartialOrder, Invoice
    }
}
