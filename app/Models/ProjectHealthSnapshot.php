<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectHealthSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHealthSnapshot extends Model
{
    /** @use HasFactory<ProjectHealthSnapshotFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'health_status',
        'completion_percentage',
        'schedule_variance',
        'budget_variance',
        'estimated_completion_date',
        'calculated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completion_percentage' => 'integer',
            'schedule_variance' => 'decimal:2',
            'budget_variance' => 'decimal:2',
            'estimated_completion_date' => 'date',
            'calculated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getHealthStatusLabelAttribute(): string
    {
        return config(
            'projects.health_statuses.'.$this->health_status,
            ucfirst(str_replace('_', ' ', (string) $this->health_status))
        );
    }
}
