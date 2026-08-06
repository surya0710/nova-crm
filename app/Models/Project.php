<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'category_id',
        'project_type_id',
        'status_id',
        'lifecycle_stage_id',
        'client_id',
        'project_number',
        'name',
        'slug',
        'description',
        'objective',
        'owner_id',
        'manager_id',
        'department_id',
        'priority',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'estimated_budget',
        'actual_budget',
        'completion_percentage',
        'metadata',
        'custom_fields',
        'settings',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_end_date' => 'date',
            'estimated_budget' => 'decimal:2',
            'actual_budget' => 'decimal:2',
            'completion_percentage' => 'integer',
            'metadata' => 'array',
            'settings' => 'array',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Alias Metadata Platform `custom_fields` onto the projects.metadata JSON column.
     */
    protected function customFields(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->metadata,
            set: fn ($value) => ['metadata' => $value],
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function lifecycleStage(): BelongsTo
    {
        return $this->belongsTo(ProjectLifecycleStage::class, 'lifecycle_stage_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sequence');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function healthSnapshots(): HasMany
    {
        return $this->hasMany(ProjectHealthSnapshot::class)->latest('calculated_at');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProgressUpdate::class)->latest();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ProjectReport::class)->latest('generated_at');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(ProjectWatcher::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ProjectMention::class)->latest();
    }

    public function calendarLinks(): HasMany
    {
        return $this->hasMany(ProjectCalendarLink::class)->latest();
    }

    public function collaborationPins(): HasMany
    {
        return $this->hasMany(ProjectCollaborationPin::class)->orderBy('sort_order');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ProjectTemplate::class, 'source_project_id');
    }

    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'portfolio_projects')
            ->withTimestamps();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_projects')
            ->withTimestamps();
    }

    public function outgoingDependencies(): HasMany
    {
        return $this->hasMany(ProjectDependency::class, 'predecessor_project_id');
    }

    public function incomingDependencies(): HasMany
    {
        return $this->hasMany(ProjectDependency::class, 'successor_project_id');
    }

    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRisk::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ProjectIssue::class);
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(ProjectBaseline::class)->orderByDesc('version');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function getPriorityLabelAttribute(): string
    {
        return config('projects.priorities.'.$this->priority, ucfirst($this->priority));
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function isReadOnly(): bool
    {
        return $this->isArchived();
    }
}
