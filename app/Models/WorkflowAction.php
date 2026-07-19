<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkflowActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowAction extends Model
{
    /** @use HasFactory<WorkflowActionFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_DISABLED];

    protected $fillable = [
        'organization_id', 'workflow_id', 'workflow_version', 'type', 'name', 'configuration',
        'status', 'position',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'workflow_version' => 'integer',
            'position' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }
}
