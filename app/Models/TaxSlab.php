<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSlab extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'tax_financial_year_id',
        'regime',
        'min_income',
        'max_income',
        'tax_percent',
        'surcharge_percent',
        'cess_percent',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'min_income' => 'decimal:2',
            'max_income' => 'decimal:2',
            'tax_percent' => 'decimal:4',
            'surcharge_percent' => 'decimal:4',
            'cess_percent' => 'decimal:4',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(TaxFinancialYear::class, 'tax_financial_year_id');
    }
}
