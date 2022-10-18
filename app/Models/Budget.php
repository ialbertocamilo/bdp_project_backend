<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;


    protected $table = 'budget';
    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['name_activity','use_grade','assignment_type','amount','project_id'];

    protected $casts = [
        'content' => 'json',
    ];


}
