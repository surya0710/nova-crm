<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectDependencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDependency extends Model
{
    /** @use HasFactory<ProjectDependencyFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'predecessor_project_id',
        'successor_project_id',
        'dependency_type',
        'lag_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lag_days' => 'integer',
        ];
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'predecessor_project_id');
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'successor_project_id');
    }
}
