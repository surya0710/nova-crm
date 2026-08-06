<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\DeliverableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deliverable extends Model
{
    /** @use HasFactory<DeliverableFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'milestone_id',
        'task_id',
        'title',
        'description',
        'status',
        'due_date',
        'completion_percentage',
        'submitted_at',
        'approved_at',
        'completed_at',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completion_percentage' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DeliverableVersion::class)->orderByDesc('version_number');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ClientApproval::class, 'approvable');
    }

    public function discussions(): MorphMany
    {
        return $this->morphMany(ClientDiscussion::class, 'discussable');
    }

    public function getStatusLabelAttribute(): string
    {
        return config('portal.deliverable_statuses.'.$this->status, ucfirst(str_replace('_', ' ', (string) $this->status)));
    }
}
