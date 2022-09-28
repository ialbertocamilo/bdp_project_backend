<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public $id;
    public $step_name;
    public $substep_name;
    public $percentage;
    public $content;


    protected $guarded = ['id'];
    protected $fillable=['uuid','step_name','substep_name','percentage','content','project_type_id', 'header_project_id'];

    protected $casts = [
        'content' => 'json',
    ];


    public function projectType(){
       return $this->belongsTo(ProjectType::class);
    }

    public function headerProject(){
        return $this->belongsTo(HeaderProject::class);
     }

    public function user(){
       return $this->belongsTo(User::class);
    }
}
