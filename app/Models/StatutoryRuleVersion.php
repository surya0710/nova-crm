<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\StatutoryRuleVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryRuleVersion extends Model
{
    /** @use HasFactory<StatutoryRuleVersionFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'statutory_rule_set_id',
        'version',
        'effective_from',
        'effective_until',
        'jurisdiction',
        'configuration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'configuration' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(StatutoryRuleSet::class, 'statutory_rule_set_id');
    }

    public function isEffectiveOn(CarbonInterface $date): bool
    {
        if ($this->effective_from->gt($date)) {
            return false;
        }

        if ($this->effective_until && $this->effective_until->lt($date)) {
            return false;
        }

        return (bool) $this->is_active;
    }
}
