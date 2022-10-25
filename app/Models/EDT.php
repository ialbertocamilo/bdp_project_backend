<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EDT extends Model
{
    use HasFactory;


    protected $table = 'edt';
    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['level','type','description','project_id'];

    protected $casts = [
        'content' => 'json',
    ];


    public function project() {
        return $this->belongsTo('App\Models\Project', 'project_id');
    }
}
