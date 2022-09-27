<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        ProjectType::create(
            ["name" => "FVC", "slug" => "fvc"]);
        ProjectType::create(
            ["name" => "DESA", "slug" => "desa"]);
    }
}
