<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectBudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBudget extends Model
{
    /** @use HasFactory<ProjectBudgetFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'name',
        'currency',
        'planned_total',
        'actual_total',
        'forecast_total',
        'variance_total',
        'status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'planned_total' => 'decimal:2',
            'actual_total' => 'decimal:2',
            'forecast_total' => 'decimal:2',
            'variance_total' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->orderBy('sort_order');
    }
}
