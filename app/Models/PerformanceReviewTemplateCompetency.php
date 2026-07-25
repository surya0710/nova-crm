<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReviewTemplateCompetency extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'review_template_id',
        'section_id',
        'competency_id',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewTemplateSection::class, 'section_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }
}
