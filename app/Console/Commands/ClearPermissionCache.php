<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class ClearPermissionCache extends Command
{
    protected $signature = 'permission:clear';
    protected $description = 'Clear Spatie permission and role cache';

    public function handle()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('✅ Permission cache cleared successfully!');
    }
}
