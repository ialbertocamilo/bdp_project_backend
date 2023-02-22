<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectData extends Model
{
    use HasFactory;



    protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['uuid','step_name','substep_name','percentage','content','project_id'];

    protected $casts = [
        'content' => 'json',
    ];


    public function projectType(){
       return $this->belongsTo(ProjectType::class);
    }

    public function user(){
       return $this->belongsTo(User::class);
    }

    public function project(){
        return $this->belongsTo(Project::class);
    }

    public function files(){
        return $this->hasMany(FileData::class);
    }
}
