<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Los roles ya fueron creados por RoleSeeder, solo los obtenemos
        $adminRole = Role::where('slug', 'administrador')->first();
        $managerRole = Role::where('slug', 'gerente')->first();
        $userRole = Role::where('slug', 'usuario')->first();
        $viewerRole = Role::where('slug', 'visualizador')->first();

        // Crear usuarios de prueba
        $users = [
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'role' => 'administrador',
                'status' => 'active'
            ],
            [
                'name' => 'Manager Test',
                'email' => 'manager@test.com',
                'password' => Hash::make('password123'),
                'role' => 'gerente',
                'status' => 'active'
            ],
            [
                'name' => 'User Test',
                'email' => 'user@test.com',
                'password' => Hash::make('password123'),
                'role' => 'usuario',
                'status' => 'active'
            ],
            [
                'name' => 'Viewer Test',
                'email' => 'viewer@test.com',
                'password' => Hash::make('password123'),
                'role' => 'visualizador',
                'status' => 'active'
            ],
            [
                'name' => 'Project Manager Test',
                'email' => 'pm@test.com',
                'password' => Hash::make('password123'),
                'role' => 'gerente',
                'status' => 'active'
            ],
            [
                'name' => 'Developer Test',
                'email' => 'dev@test.com',
                'password' => Hash::make('password123'),
                'role' => 'usuario',
                'status' => 'active'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'status' => $userData['status'],
                    'email_verified_at' => now()
                ]
            );

            // Asignar rol
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            $this->command->info("Usuario creado: {$user->name} ({$user->email}) - Rol: {$userData['role']}");
        }

        $this->command->info('Usuarios de prueba creados exitosamente!');
        $this->command->info('Credenciales:');
        $this->command->info('Admin: admin@test.com / password123');
        $this->command->info('Manager: manager@test.com / password123');
        $this->command->info('User: user@test.com / password123');
        $this->command->info('Viewer: viewer@test.com / password123');
        $this->command->info('PM: pm@test.com / password123');
        $this->command->info('Dev: dev@test.com / password123');
    }
}