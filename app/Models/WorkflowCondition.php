<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkflowConditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowCondition extends Model
{
    /** @use HasFactory<WorkflowConditionFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    public const TYPE_GROUP = 'group';

    public const TYPE_CONDITION = 'condition';

    public const TYPES = [self::TYPE_GROUP, self::TYPE_CONDITION];

    public const BOOLEAN_OPERATORS = ['all', 'any'];

    protected $fillable = [
        'organization_id', 'workflow_id', 'workflow_version', 'parent_condition_id', 'type',
        'boolean_operator', 'field', 'operator', 'value', 'negated', 'position',
    ];

    protected function casts(): array
    {
        return ['value' => 'array', 'workflow_version' => 'integer', 'negated' => 'boolean', 'position' => 'integer'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_condition_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_condition_id')->withTrashed()->orderBy('position');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
}
