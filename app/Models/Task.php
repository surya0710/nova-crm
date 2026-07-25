<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'parent_task_id',
        'milestone_id',
        'status_id',
        'priority_id',
        'task_number',
        'slug',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'completed_at',
        'assigned_to',
        'assigned_by',
        'estimated_hours',
        'actual_hours',
        'start_date',
        'due_date',
        'completion_percentage',
        'metadata',
        'settings',
        'sort_order',
        'is_archived',
        'taskable_type',
        'taskable_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
            'completion_percentage' => 'integer',
            'metadata' => 'array',
            'settings' => 'array',
            'sort_order' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Alias Metadata Platform `custom_fields` onto the tasks.metadata JSON column.
     */
    protected function customFields(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->metadata,
            set: fn ($value) => ['metadata' => $value],
        );
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('sort_order');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function taskStatus(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function taskPriority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class, 'priority_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function predecessorDependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'successor_task_id');
    }

    public function successorDependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'predecessor_task_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('sequence');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class)->latest('start_time');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ProjectLabel::class, 'task_labels', 'task_id', 'label_id')
            ->using(TaskLabel::class)
            ->withTimestamps();
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(TaskRecurrence::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ProjectMention::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->taskStatus) {
            return $this->taskStatus->name;
        }

        return config('tasks.statuses.'.$this->status, ucfirst(str_replace('_', ' ', (string) $this->status)));
    }

    public function getPriorityLabelAttribute(): string
    {
        if ($this->taskPriority) {
            return $this->taskPriority->name;
        }

        return config('tasks.priorities.'.$this->priority, ucfirst((string) $this->priority));
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function isClosed(): bool
    {
        if ($this->relationLoaded('taskStatus') && $this->taskStatus) {
            return (bool) $this->taskStatus->is_closed;
        }

        if ($this->status_id) {
            $closed = TaskStatus::query()->whereKey($this->status_id)->value('is_closed');
            if ($closed !== null) {
                return (bool) $closed;
            }
        }

        return in_array($this->status, ['completed', 'cancelled'], true);
    }

    public function isOpen(): bool
    {
        return ! $this->isClosed() && ! $this->isArchived();
    }

    public function isOverdue(): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        if ($this->due_date) {
            return $this->due_date->isPast();
        }

        return $this->due_at && $this->due_at->isPast();
    }

    public function isReadOnly(): bool
    {
        return $this->isArchived();
    }
}
