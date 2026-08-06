<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\TenantContext;
use Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_DISABLED];

    protected $fillable = [
        'organization_id', 'name', 'description', 'trigger_type', 'trigger_config',
        'status', 'version', 'concurrency_limit', 'execution_timeout_seconds',
        'enabled_at', 'enabled_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'version' => 'integer',
            'concurrency_limit' => 'integer',
            'execution_timeout_seconds' => 'integer',
            'enabled_at' => 'datetime',
        ];
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowCondition::class)->orderBy('position');
    }

    public function rootConditions(): HasMany
    {
        return $this->conditions()->whereNull('parent_condition_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('position');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $organizationId = null;
        if (request()->hasSession()) {
            $sessionOrganizationId = request()->session()->get('current_organization_id');
            $organizationId = is_numeric($sessionOrganizationId) ? (int) $sessionOrganizationId : null;
        }
        $organizationId ??= app(TenantContext::class)->id();

        return $this->newQuery()
            ->when($organizationId !== null, fn ($query) => $query->where('organization_id', $organizationId))
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
