<?php

namespace Database\Seeders;

use App\Models\User;
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
            'dashboard.view',
            'users.manage',
            'settings.manage',
            'locations.manage',
            'surveys.manage',
            'measurements.create',
            'measurements.import',
            'heatmaps.generate',
            'insights.review',
            'reports.generate',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'Super Admin' => $permissions,
            'Network Admin' => array_values(array_diff($permissions, ['users.manage'])),
            'Surveyor' => ['dashboard.view', 'locations.manage', 'surveys.manage', 'measurements.create', 'measurements.import', 'heatmaps.generate'],
            'Analyst' => ['dashboard.view', 'insights.review', 'reports.generate'],
            'Viewer' => ['dashboard.view'],
        ];

        foreach ($roles as $name => $granted) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($granted);
        }

        $email = config('signalgrid.admin_email');
        $password = config('signalgrid.admin_password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $name = config('signalgrid.admin_name');

            $user = User::create([
                'name' => is_string($name) && $name !== '' ? $name : 'SignalGrid Administrator',
                'email' => $email,
                'password' => $password,
                'status' => 'active',
            ]);
        }

        if (! $user->hasRole('Super Admin')) {
            $user->syncRoles(['Super Admin']);
        }
    }
}
