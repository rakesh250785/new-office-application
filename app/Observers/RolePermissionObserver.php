<?php

namespace App\Observers;

use Spatie\Permission\PermissionRegistrar;

class RolePermissionObserver
{
    public function saved($model)
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function deleted($model)
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
