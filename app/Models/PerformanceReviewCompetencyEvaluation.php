<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReviewCompetencyEvaluation extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'performance_review_id',
        'competency_id',
        'competency_name',
        'competency_code',
        'section_name',
        'weightage',
        'sort_order',
        'rating',
        'comments',
        'reviewer_notes',
    ];

    protected function casts(): array
    {
        return [
            'performance_review_id' => 'integer',
            'competency_id' => 'integer',
            'weightage' => 'decimal:4',
            'sort_order' => 'integer',
            'rating' => 'decimal:2',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }
}
