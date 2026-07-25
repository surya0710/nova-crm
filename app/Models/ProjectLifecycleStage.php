<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectLifecycleStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLifecycleStage extends Model
{
    /** @use HasFactory<ProjectLifecycleStageFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'sequence',
        'color',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'lifecycle_stage_id');
    }
}
