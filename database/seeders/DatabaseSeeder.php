<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Crear roles primero
        $this->call(RoleSeeder::class);
        
        // Crear usuario administrador principal
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123'),
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );

        // Asignar rol de administrador
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && !$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        $this->command->info('Usuario administrador creado: admin@admin.com / 123');
        
        // Crear tipos de proyecto
        $this->call(ProjectTypeSeeder::class);
        
        // Crear usuarios de prueba (opcional)
        $this->call(TestUsersSeeder::class);
    }
}
