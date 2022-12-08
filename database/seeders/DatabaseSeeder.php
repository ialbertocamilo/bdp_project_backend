<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
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
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);;
        $user=new User();
        $user->name="admin";
        $user->email="admin@admin.com";
        $user->password=Hash::make('123');
        $user->save();
        $this->call(ProjectTypeSeeder::class);
        $this->call(RoleSeeder::class);
    }
}
