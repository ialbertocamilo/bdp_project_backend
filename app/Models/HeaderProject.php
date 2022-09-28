<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderProject extends Model
{
    use HasFactory;

    public $id;
    public $step_name;
    public $substep_name;
    public $percentage;
    public $content;


    protected $guarded = ['id'];
    protected $fillable=['uuid','name'];

    protected $casts = [
        'content' => 'json',
    ];
   
}
