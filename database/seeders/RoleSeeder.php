<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(
            ["name" => "Gestor"]);
        Role::create(
            ["name" => "Supervisor"]);
        Role::create(
            ["name" => "Auditor"]);

        $user = User::find(1);
        $user->syncRoles(2);
        $user->update();
    }
}
