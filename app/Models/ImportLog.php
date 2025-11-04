<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'status',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'errors',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'errors' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
