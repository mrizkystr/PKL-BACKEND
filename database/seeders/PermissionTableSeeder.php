<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    protected $adminPermissions = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.import',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.update',
        'permissions.delete',
        'data-ps.view',
        'data-ps.create',
        'data-ps.update',
        'data-ps.delete',
        'data-ps.import',
        'sales-codes.view',
        'sales-codes.create',
        'sales-codes.update',
        'sales-codes.delete',
        'sales-codes.import',
        'set-target.create',
        'set-target.update',
        'set-target.delete',
        'set-target.view',
    ];

    protected $userPermissions = [
        'set-target.view',
        'sales-codes.view',
        'data-ps.view',
        'set-target.view',
    ];

    protected $salesPermissions = [
        'data-ps.view',
        'data-ps.create',
        'data-ps.update',
        'data-ps.delete',
        'data-ps.import',
        'set-target.create',
        'set-target.update',
        'set-target.delete',
        'set-target.view',
    ];

    public function run()
    {
        $this->createRoleWithPermissions('admin', $this->adminPermissions);
        $this->createRoleWithPermissions('user', $this->userPermissions);
        $this->createRoleWithPermissions('sales', $this->salesPermissions);
    }

    protected function createRoleWithPermissions($role, $permissions)
    {
        // Create the role
        $roleId = DB::table('roles')->insertGetId(['name' => $role]);

        // Attach permissions to the role
        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission,
                'role_id' => $roleId,
            ]);
        }
    }
}
