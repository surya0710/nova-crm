<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PerformanceReviewTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReviewTemplate extends Model
{
    /** @use HasFactory<PerformanceReviewTemplateFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PerformanceReviewTemplateSection::class, 'review_template_id')
            ->orderBy('sort_order');
    }

    public function templateCompetencies(): HasMany
    {
        return $this->hasMany(PerformanceReviewTemplateCompetency::class, 'review_template_id')
            ->orderBy('sort_order');
    }
}
