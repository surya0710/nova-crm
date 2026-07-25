<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PerformanceConfigurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceConfiguration extends Model
{
    /** @use HasFactory<PerformanceConfigurationFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'default_review_frequency',
        'rating_scale_id',
        'goal_weighting',
        'competency_weighting',
        'review_visibility',
        'calibration_enabled',
        'feedback_anonymous_enabled',
        'feedback_anonymous_required',
    ];

    protected function casts(): array
    {
        return [
            'rating_scale_id' => 'integer',
            'goal_weighting' => 'decimal:4',
            'competency_weighting' => 'decimal:4',
            'calibration_enabled' => 'boolean',
            'feedback_anonymous_enabled' => 'boolean',
            'feedback_anonymous_required' => 'boolean',
        ];
    }

    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(PerformanceRatingScale::class, 'rating_scale_id');
    }
}
