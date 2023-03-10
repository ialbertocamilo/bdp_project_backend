<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileData extends Model
{
    use HasFactory;
    protected $fillable=['uuid','project_id','name_field','step','sub_step','route','size'];

    private function project(){
        return $this->hasMany(Project::class);
    }
}
