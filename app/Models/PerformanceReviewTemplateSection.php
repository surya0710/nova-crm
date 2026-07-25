<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReviewTemplateSection extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'review_template_id',
        'name',
        'instructions',
        'weightage',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weightage' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewTemplate::class, 'review_template_id');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(PerformanceReviewTemplateCompetency::class, 'section_id')
            ->orderBy('sort_order');
    }
}
