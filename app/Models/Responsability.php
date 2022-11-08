<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsability extends Model
{
    use HasFactory;


    protected $table = 'responsability';
    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['member','responsability','edt_id'];

    protected $casts = [
        'content' => 'json',
    ];

    public function componentEDT() {
        return $this->belongsTo('App\Models\EDT','edt_id' );
    }


}
