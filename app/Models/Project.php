<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;


//    protected $guarded  = ['id'];
    protected $hidden   = ['project_type_id'];
    protected $fillable = ['name', 'uuid', 'description', 'project_type_id'];

    protected $with = ['projectType', 'projectData', 'files'];

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function files()
    {
        return $this->hasMany(FileData::class);
    }

    public function projectData()
    {
        return $this->hasMany(ProjectData::class);
    }
}
