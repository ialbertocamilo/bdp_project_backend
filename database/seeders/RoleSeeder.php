<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Acceso completo al sistema',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'projects.delete',
                    'reports.view',
                    'reports.generate',
                    'dashboard.view',
                    'dashboard.edit',
                    'settings.view',
                    'settings.edit',
                    'roles.view',
                    'roles.edit'
                ]
            ],
            [
                'name' => 'Gerente',
                'slug' => 'manager',
                'description' => 'Gestión de proyectos y usuarios',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.edit',
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'reports.view',
                    'reports.generate',
                    'dashboard.view'
                ]
            ],
            [
                'name' => 'Usuario',
                'slug' => 'user',
                'description' => 'Usuario estándar del sistema',
                'permissions' => [
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'reports.view',
                    'dashboard.view'
                ]
            ],
            [
                'name' => 'Visualizador',
                'slug' => 'viewer',
                'description' => 'Solo lectura del sistema',
                'permissions' => [
                    'projects.view',
                    'reports.view',
                    'dashboard.view'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        $this->command->info('Roles creados exitosamente!');
    }
}