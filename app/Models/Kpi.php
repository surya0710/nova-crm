<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\KpiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kpi extends Model
{
    /** @use HasFactory<KpiFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'unit',
        'measurement_type',
        'default_target',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_target' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'kpi_id');
    }
}
