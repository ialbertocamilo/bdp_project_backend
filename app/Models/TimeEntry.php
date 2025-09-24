<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'description',
        'start_time',
        'end_time',
        'hours',
        'date',
        'is_billable'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'date' => 'date',
        'hours' => 'decimal:2',
        'is_billable' => 'boolean'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($timeEntry) {
            $timeEntry->user_id = auth()->id();
            $timeEntry->date = $timeEntry->date ?? now()->toDateString();
        });

        static::saved(function ($timeEntry) {
            // Update task actual hours when time entry is saved
            $timeEntry->task->actual_hours = $timeEntry->task->timeEntries()->sum('hours');
            $timeEntry->task->save();
        });
    }
}