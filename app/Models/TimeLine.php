<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLine extends Model
{
    use HasFactory;

    protected $table = 'time_line';

    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['name_activity','date_ini','date_end','project_id'];

    protected $casts = [
        'content' => 'json',
    ];


}
