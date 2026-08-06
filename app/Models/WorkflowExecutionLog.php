<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkflowExecutionLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecutionLog extends Model
{
    /** @use HasFactory<WorkflowExecutionLogFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'workflow_execution_id', 'workflow_action_id',
        'workflow_condition_id', 'level', 'event', 'status', 'message',
        'context', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'occurred_at' => 'datetime'];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class, 'workflow_execution_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(WorkflowAction::class, 'workflow_action_id')->withTrashed();
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(WorkflowCondition::class, 'workflow_condition_id')->withTrashed();
    }
}
