<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProgressUpdateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressUpdate extends Model
{
    /** @use HasFactory<ProgressUpdateFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'milestone_id',
        'updated_by',
        'progress_percentage',
        'summary',
        'blockers',
        'next_steps',
        'metadata',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Alias Metadata Platform `custom_fields` onto the progress_updates.metadata JSON column.
     */
    protected function customFields(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->metadata,
            set: fn ($value) => ['metadata' => $value],
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
