<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AssignmentRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentRule extends Model
{
    /** @use HasFactory<AssignmentRuleFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'entity_type',
        'priority',
        'is_active',
        'is_default',
        'strategy',
        'assignment_pool_id',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'conditions' => 'array',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(AssignmentPool::class, 'assignment_pool_id');
    }

    /**
     * Effective strategy: rule override, else pool strategy.
     */
    public function resolvedStrategy(): ?string
    {
        if ($this->strategy) {
            return $this->strategy;
        }

        return $this->pool?->strategy;
    }
}
