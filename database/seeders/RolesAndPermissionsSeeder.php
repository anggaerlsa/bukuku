<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'manage roles',
            'manage genres',
            'manage worlds',   // manage every world, not just your own
            'create worlds',
            'edit own worlds',
            'delete own worlds',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Superadmin — holds every authority.
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->syncPermissions($permissions);

        // Admin — manages all worlds, genres, and users.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'manage users',
            'manage genres',
            'manage worlds',
            'create worlds',
            'edit own worlds',
            'delete own worlds',
        ]);

        // Author — the writer: builds and tends their own worlds only.
        $author = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        $author->syncPermissions([
            'create worlds',
            'edit own worlds',
            'delete own worlds',
        ]);
    }
}
