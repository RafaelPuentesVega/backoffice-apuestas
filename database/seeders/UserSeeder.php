<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'ver_usuarios']);
        Permission::create(['name' => 'crear_usuarios']);
        Permission::create(['name' => 'editar_usuarios']);
        Permission::create(['name' => 'eliminar_usuarios']);

        Permission::create(['name' => 'ver_libros']);
        Permission::create(['name' => 'crear_libros']);

        $adminUser = User::query()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => '12345',
            'email_verified_at' => now()
        ]);

        $roleAdmin = Role::create(['name' => 'super-admin']);
        $adminUser->assignRole($roleAdmin);
        $permissionsAdmin = Permission::query()->pluck('name');
        $roleAdmin->syncPermissions($permissionsAdmin);

        $studentUser = User::query()->create([
            'name' => 'student',
            'email' => 'student@student.com',
            'password' => '12345',
            'email_verified_at' => now()
        ]);

        $roleStudent = Role::create(['name' => 'student']);
        $studentUser->assignRole($roleStudent);
        $roleStudent->syncPermissions(['ver_libros']);
    }
}
