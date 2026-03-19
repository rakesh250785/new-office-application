<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear cached permissions & roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define modules and permissions
        $modulesPermissions = [
            'website_dashboard' => ['view_website_dashboard', 'edit_website_dashboard'],
            'expenses' => ['view_own_expanses', 'view_expenses', 'add_new_expenses', 'view_history', 'export_expenses', 'edit_expenses', 'delete_expenses', 'download_expenses_bill', 'sale_team', 'service_team'],
            'financial_report' => ['view_own_financial_report', 'view_financial_report', 'export_financial_report', 'financial_approved_action'],
            'performance_report' => ['view_own_performance_report', 'view_performance_report', 'export_performance_report'],
            'order_summary' => ['view_own_order_summary', 'view_order_summary'],
            'quotation_summary' => ['view_own_quotation_summary', 'view_quotation_summary'],
            'quotation_detail' => [
                'view_own_quotation_detail',
                'view_quotation_detail',
                'add_quotation_detail',
                'export_quotation_detail',
                'view_quotation_detail_reason',
                'edit_quotation_detail_reason',
                'generate_quotation_detail_order',
                'download_quotation_detail',
                'edit_quotation_detail',
                'delete_quotation_detail',
            ],
            'quotation_report' => ['view_own_quotation_report', 'view_quotation_report', 'export_quotation_report'],
            'order' => [
                'view_own_order',
                'view_order',
                'export_order',
                'add_order',
                'delete_order',
                'view_order_reason',
                'edit_order_reason',
                'generate_partial_order',
                'close_order',
                'download_order',
            ],
            'partial_order' => [
                'view_own_partial_order',
                'view_partial_order',
                'edit_partial_order',
                'delete_partial_order',
                'export_partial_order', 
                'upload_partial_order',
            ],
            'order_report' => [
                'view_own_order_report',
                'view_order_report',
                'export_order_report',
            ],
            'invoice' => ['view_own_invoice', 'view_invoice', 'export_invoice', 'view_pod_doc', 'upload_pod_doc'],
            'owner' => ['view_own_owner', 'view_owner', 'export_owner', 'edit_owner', 'delete_owner', 'add_owner'],
            'customer' => ['view_own_customer', 'view_customer', 'export_customer', 'edit_customer', 'delete_customer', 'add_customer'],
            'user' => ['view_own_user', 'view_user', 'export_user', 'edit_user', 'delete_user', 'add_user'],
            'role_access' => ['view_role_access', 'edit_role_access', 'delete_role_access', 'add_role_access'],
            'product' => [
                'view_own_product',
                'view_product',
                'export_product',
                'edit_product',
                'delete_product',
                'add_product',
                'import_product_quantity',
                'import_product_price',
            ],
            'parameter' => ['view_own_parameter', 'view_parameter', 'export_parameter', 'edit_parameter', 'delete_parameter', 'add_parameter'],
            'usp' => ['view_own_usp', 'view_usp', 'export_usp', 'edit_usp', 'delete_usp', 'add_usp'],
            'brand' => ['view_own_brand', 'view_brand', 'export_brand', 'edit_brand', 'delete_brand', 'add_brand'],
            'category' => ['view_own_category', 'view_category', 'export_category', 'edit_category', 'delete_category', 'add_category'],
            'quotation_format' => [
                'view_own_quotation_format',
                'view_quotation_format',
                'export_quotation_format',
                'edit_quotation_format',
                'delete_quotation_format',
                'add_quotation_format',
            ],
            'principal' => ['view_own_principal', 'view_principal', 'export_principal', 'edit_principal', 'delete_principal', 'add_principal'],
            'reason' => ['view_own_reason', 'view_reason', 'export_reason', 'edit_reason', 'delete_reason', 'add_reason'],
            'notifications' => ['view_notifications', 'delete_notifications'],
            'source' => ['view_own_source', 'view_source', 'export_source', 'edit_source', 'delete_source', 'add_source'],
            'supplier' => ['view_own_supplier', 'view_supplier', 'export_supplier', 'edit_supplier', 'delete_supplier', 'add_supplier'],
            'courier' => ['view_own_courier', 'view_courier', 'export_courier', 'edit_courier', 'delete_courier', 'add_courier'],
        ];

        // Create permissions with module_name
        foreach ($modulesPermissions as $module => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission],
                    ['module_name' => $module]
                );
            }
        }

        // Create default admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'branch_id' => 1]);

        // Assign all permissions to admin role
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        // Assign admin role to user with email admin@office.com
        $adminUser = User::where('email', 'admin@office.com')->first();
        if ($adminUser && ! $adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }
    }
}
