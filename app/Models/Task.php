<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'parent_task_id',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'end_date',
        'estimated_hours',
        'actual_hours',
        'progress_percentage',
        'dependencies',
        'tags',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress_percentage' => 'integer',
        'dependencies' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByAssignee($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', now())
                    ->where('status', '!=', 'completed');
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereBetween('end_date', [now(), now()->addDays($days)])
                    ->where('status', '!=', 'completed');
    }

    // Accessors & Mutators
    public function getIsOverdueAttribute()
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'completed';
    }

    public function getIsDueSoonAttribute()
    {
        return $this->end_date && $this->end_date->isBetween(now(), now()->addDays(7)) && $this->status !== 'completed';
    }

    public function getDurationInDaysAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date) + 1;
        }
        return 0;
    }

    public function getCompletionStatusAttribute()
    {
        if ($this->status === 'completed') {
            return 'completed';
        } elseif ($this->is_overdue) {
            return 'overdue';
        } elseif ($this->is_due_soon) {
            return 'due_soon';
        } else {
            return 'on_track';
        }
    }

    // Methods
    public function updateProgress($percentage)
    {
        $this->progress_percentage = max(0, min(100, $percentage));
        
        if ($percentage >= 100) {
            $this->status = 'completed';
        } elseif ($percentage > 0 && $this->status === 'not_started') {
            $this->status = 'in_progress';
        }
        
        $this->save();
        
        // Update parent task progress if exists
        if ($this->parent_task_id) {
            $this->updateParentProgress();
        }
    }

    public function updateParentProgress()
    {
        if (!$this->parentTask) return;

        $siblings = $this->parentTask->subtasks;
        $totalProgress = $siblings->sum('progress_percentage');
        $averageProgress = $siblings->count() > 0 ? $totalProgress / $siblings->count() : 0;
        
        $this->parentTask->updateProgress($averageProgress);
    }

    public function addDependency($taskId)
    {
        $dependencies = $this->dependencies ?? [];
        if (!in_array($taskId, $dependencies)) {
            $dependencies[] = $taskId;
            $this->dependencies = $dependencies;
            $this->save();
        }
    }

    public function removeDependency($taskId)
    {
        $dependencies = $this->dependencies ?? [];
        $this->dependencies = array_values(array_filter($dependencies, fn($id) => $id !== $taskId));
        $this->save();
    }

    public function canStart()
    {
        if (!$this->dependencies) return true;

        $dependentTasks = Task::whereIn('id', $this->dependencies)->get();
        return $dependentTasks->every(fn($task) => $task->status === 'completed');
    }

    public function getGanttData()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start' => $this->start_date?->format('Y-m-d'),
            'end' => $this->end_date?->format('Y-m-d'),
            'progress' => $this->progress_percentage,
            'dependencies' => $this->dependencies ?? [],
            'assignee' => $this->assignedUser?->name,
            'status' => $this->status,
            'priority' => $this->priority,
            'duration' => $this->duration_in_days,
            'parent' => $this->parent_task_id,
            'can_start' => $this->canStart()
        ];
    }

    public static function getStatuses()
    {
        return [
            'not_started' => 'No Iniciada',
            'in_progress' => 'En Progreso',
            'on_hold' => 'En Pausa',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada'
        ];
    }

    public static function getPriorities()
    {
        return [
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica'
        ];
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            $task->created_by = auth()->id();
            $task->status = $task->status ?? 'not_started';
            $task->progress_percentage = $task->progress_percentage ?? 0;
        });

        static::updating(function ($task) {
            $task->updated_by = auth()->id();
        });

        static::deleting(function ($task) {
            // When deleting a task, move subtasks to the parent or make them top-level
            $task->subtasks()->update(['parent_task_id' => $task->parent_task_id]);
        });
    }
}