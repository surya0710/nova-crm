<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTemplate extends Model
{
    /** @use HasFactory<ProjectTemplateFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'category',
        'industry',
        'department_id',
        'source_project_id',
        'created_by',
        'is_system',
        'is_favorite',
        'version',
        'usage_count',
        'defaults',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_favorite' => 'boolean',
            'version' => 'integer',
            'usage_count' => 'integer',
            'defaults' => 'array',
            'metadata' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sourceProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'source_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateMilestones(): HasMany
    {
        return $this->hasMany(TemplateMilestone::class)->orderBy('sequence');
    }

    public function templateTasks(): HasMany
    {
        return $this->hasMany(TemplateTask::class)->orderBy('sort_order');
    }

    public function templateLabels(): HasMany
    {
        return $this->hasMany(TemplateLabel::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $organizationId = app(\App\Services\TenantContext::class)->id();

        return static::query()
            ->withoutGlobalScopes()
            ->where($field, $value)
            ->where(function ($query) use ($organizationId) {
                if ($organizationId) {
                    $query->where('organization_id', $organizationId)
                        ->orWhere(function ($system) {
                            $system->whereNull('organization_id')->where('is_system', true);
                        });
                } else {
                    $query->whereNull('organization_id')->where('is_system', true);
                }
            })
            ->firstOrFail();
    }
}
