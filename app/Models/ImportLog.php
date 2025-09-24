<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'file_type',
        'file_size',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'errors',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now()
        ]);
    }

    public function markAsCompleted($importedRows, $errors = [])
    {
        $this->update([
            'status' => empty($errors) ? 'completed' : 'completed_with_errors',
            'imported_rows' => $importedRows,
            'failed_rows' => count($errors),
            'errors' => $errors,
            'completed_at' => now()
        ]);
    }

    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'errors' => [$error],
            'completed_at' => now()
        ]);
    }
}