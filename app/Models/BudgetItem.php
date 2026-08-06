<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'project_budget_id',
        'budget_category_id',
        'name',
        'planned',
        'actual',
        'forecast',
        'variance',
        'currency',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'planned' => 'decimal:2',
            'actual' => 'decimal:2',
            'forecast' => 'decimal:2',
            'variance' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }
}
