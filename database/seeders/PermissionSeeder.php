<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions grouped by module
        $permissions = [
            // Dashboard
            'dashboard' => [
                'view-dashboard',
                'analytics'
            ],

            // Users Management
            'users' => [
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
                'manage-user-roles',
                'view-user-profile',
                'edit-user-profile',
                'reset-user-password',
                'activate-deactivate-users',
            ],

            // Roles & Permissions
            'roles' => [
                'view-roles',
                'create-roles',
                'edit-roles',
                'delete-roles',
                'assign-permissions-to-roles',
            ],
            'permissions' => [
                'view-permissions',
                'create-permissions',
                'edit-permissions',
                'delete-permissions',
            ],

            // Sellers Management
            'sellers' => [
                'view-sellers',
                'create-sellers',
                'edit-sellers',
                'delete-sellers',
                'approve-sellers',
                'suspend-sellers',
                'view-seller-applications',
                'approve-seller-applications',
                'delete-seller-applications',
                'reject-seller-applications',
                'view-verification-documents',
                'approve-verification-documents',
                'reject-verification-documents',
            ],

            // Products Management
            'products' => [
                'view-products',
                'create-products',
                'edit-products',
                'delete-products',
                'publish-products',
                'unpublish-products',
                'approve-products',
                'reject-products',
                'manage-product-inventory',
                'view-product-analytics',
                'import-products',
                'export-products',
            ],

            // Categories Management
            'categories' => [
                'view-categories',
                'create-categories',
                'edit-categories',
                'delete-categories',
                'manage-category-hierarchy',
                'reorder-categories',
            ],

            // Subcategories Management
            'subcategories' => [
                'view-subcategories',
                'create-subcategories',
                'edit-subcategories',
                'delete-subcategories',
                'assign-subcategories-to-categories',
            ],

            // Brands Management
            'brands' => [
                'view-brands',
                'create-brands',
                'edit-brands',
                'delete-brands',
                'approve-brands',
                'feature-brands',
            ],

            // Sub-brands Management
            'subbrands' => [
                'view-subbrands',
                'create-subbrands',
                'edit-subbrands',
                'delete-subbrands',
                'assign-subbrands-to-brands',
            ],

            // Inventory Management
            'inventory' => [
                'view-inventory',
                'manage-inventory',
                'adjust-inventory',
//                'view-inventory-reports',
                'manage-stock-alerts',
                'transfer-inventory',
                'view-inventory-history',
            ],

            // Warehouses Management
            'warehouses' => [
                'view-warehouses',
                'create-warehouses',
                'edit-warehouses',
                'delete-warehouses',
                'manage-warehouse-staff',
                'assign-products-to-warehouses',
                'view-warehouse-reports',
            ],

            // Branches Management
            'branches' => [
                'view-branches',
                'create-branches',
                'edit-branches',
                'delete-branches',
                'manage-branch-staff',
                'assign-inventory-to-branches',
                'view-branch-reports',
            ],

            // Units Management
            'units' => [
                'view-units',
                'create-units',
                'edit-units',
                'delete-units',
            ],

            // Variants Management
            'variants' => [
                'view-variants',
                'create-variants',
                'edit-variants',
                'delete-variants',
                'manage-variant-options',
            ],

            // Orders Management
            'orders' => [
                'view-orders',
                'create-orders',
                'edit-orders',
                'cancel-orders',
                'process-orders',
                'ship-orders',
                'deliver-orders',
                'refund-orders',
                'view-order-analytics',
            ],

            // Payments Management
            'payments' => [
                'view-payments',
                'process-payments',
                'refund-payments',
                'view-payment-reports',
                'manage-payment-methods',
            ],

            // Reports & Analytics
            'reports' => [
                'view-sales-reports',
                'view-inventory-reports',
                'view-user-reports',
                'view-seller-reports',
                'view-product-reports',
                'view-financial-reports',
                'export-reports',
            ],

            // System Settings
            'settings' => [
                'view-system-settings',
                'edit-system-settings',
                'manage-email-templates',
                'manage-notifications',
                'view-system-logs',
                'backup-system',
                'restore-system',
            ],

            // Communications
            'communications' => [
                'send-notifications',
                'send-emails',
                'send-sms',
                'manage-communication-templates',
                'view-communication-logs',
            ],

            'customers'=>[
                'view-customers',
                'create-customers',
                'edit-customers',
                'delete-customers',
                'manage-customer-hierarchy',
                'reorder-customers',
                'view-customer-analytics',
                'suspend_customers',
                'activate_customers',
            ],
            'commissions'=>[
                'view-commissions',
                'create-commissions',
                'edit-commissions',
                'delete-commissions',
                'manage-commissions',
                'reorder-commissions',
                'view-commission-reports',
                'manage-commission-reports',
            ],

            // Regions Management
            'regions' => [
                'view-regions',
                'create-regions',
                'edit-regions',
                'delete-regions',
            ],

        ];

        // Create all permissions
        foreach ($permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'module' => $module,
                ]);
            }
        }

        // Create default roles
        $this->createDefaultRoles();
    }

    private function createDefaultRoles()
    {
        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ]);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except super admin specific ones
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $adminPermissions = Permission::whereNotIn('name', [
            'backup-system',
            'restore-system',
            'view-system-logs',
        ])->get();
        $admin->givePermissionTo($adminPermissions);

        // Manager - has management permissions
        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web'
        ]);
        $managerPermissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products', 'edit-products', 'approve-products',
            'view-sellers', 'approve-sellers',
            'view-orders', 'process-orders',
            'view-inventory', 'manage-inventory',
            'view-reports',
        ])->get();
        $manager->givePermissionTo($managerPermissions);

        // Staff - has basic operational permissions
        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web'
        ]);
        $staffPermissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products',
            'view-orders', 'process-orders',
            'view-inventory',
        ])->get();
        $staff->givePermissionTo($staffPermissions);

        // Seller - has seller specific permissions
        $seller = Role::firstOrCreate([
            'name' => 'seller',
            'guard_name' => 'web'
        ]);
        $sellerPermissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products', 'create-products', 'edit-products',
            'manage-product-inventory',
            'view-orders',
            'view-user-profile', 'edit-user-profile',
        ])->get();
        $seller->givePermissionTo($sellerPermissions);

        // Basic User - minimal permissions
        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web'
        ]);
        $userPermissions = Permission::whereIn('name', [
            'view-user-profile',
            'edit-user-profile',
        ])->get();
        $user->givePermissionTo($userPermissions);
    }
}
