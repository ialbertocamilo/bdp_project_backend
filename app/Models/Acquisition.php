<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acquisition extends Model
{
    use HasFactory;


    protected $table = 'acquisition';
    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['acquisition','modality','date_ini','date_end','amount','edt_id'];

    protected $casts = [
        'content' => 'json',
    ];

    public function componentEDT() {
        return $this->belongsTo('App\Models\EDT','edt_id' );
    }


}
