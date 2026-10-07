<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the cached roles and permissions before changing them
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
        ];

        // Clear the cache again so the new permissions can be found by name.
        // (DatabaseSeeder uses WithoutModelEvents, which stops Spatie clearing it automatically.)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('super-admin')->syncPermissions($permissions);

        Role::findOrCreate('admin')->syncPermissions($permissions);

        Role::findOrCreate('staff')->syncPermissions(['view-users']);
    }
}
