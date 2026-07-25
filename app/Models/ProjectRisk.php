<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectRiskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRisk extends Model
{
    /** @use HasFactory<ProjectRiskFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'portfolio_id',
        'program_id',
        'title',
        'description',
        'category',
        'probability',
        'impact',
        'severity',
        'mitigation_plan',
        'contingency_plan',
        'owner_id',
        'due_date',
        'status',
        'escalated_at',
        'history',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'probability' => 'integer',
            'impact' => 'integer',
            'severity' => 'integer',
            'due_date' => 'date',
            'escalated_at' => 'datetime',
            'history' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProjectRisk $risk): void {
            $risk->severity = static::computeSeverity(
                (int) $risk->probability,
                (int) $risk->impact
            );
        });
    }

    public static function computeSeverity(int $probability, int $impact): int
    {
        return $probability * $impact;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
