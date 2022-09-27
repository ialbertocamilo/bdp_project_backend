<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    use HasFactory;

    const FVC=1;
    const DESA=2;

    public function projects(){
        return  $this->hasMany(Project::class);
    }

}
