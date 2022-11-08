<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Risk extends Model
{
    use HasFactory;


    protected $table = 'risk';
    // protected $hidden=['project_id'];
    protected $guarded = ['id'];
    protected $fillable=['type','description','i','p','c','value','level','edt_id'];

    protected $casts = [
        'content' => 'json',
    ];

    public function componentEDT() {
        return $this->belongsTo('App\Models\EDT','edt_id' );
    }


}
