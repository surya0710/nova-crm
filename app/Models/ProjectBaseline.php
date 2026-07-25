<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectBaselineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBaseline extends Model
{
    /** @use HasFactory<ProjectBaselineFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'version',
        'name',
        'scope_snapshot',
        'schedule_snapshot',
        'budget_snapshot',
        'progress_snapshot',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'scope_snapshot' => 'array',
            'schedule_snapshot' => 'array',
            'budget_snapshot' => 'array',
            'progress_snapshot' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
