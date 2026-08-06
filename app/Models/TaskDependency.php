<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TaskDependencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    /** @use HasFactory<TaskDependencyFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'predecessor_task_id',
        'successor_task_id',
        'dependency_type',
    ];

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_task_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_task_id');
    }

    public function getDependencyTypeLabelAttribute(): string
    {
        return config('tasks.dependency_types.'.$this->dependency_type, ucfirst(str_replace('_', ' ', $this->dependency_type)));
    }
}
