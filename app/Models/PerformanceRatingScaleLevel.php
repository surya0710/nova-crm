<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceRatingScaleLevel extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'rating_scale_id',
        'value',
        'label',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function ratingScale(): BelongsTo
    {
        return $this->belongsTo(PerformanceRatingScale::class, 'rating_scale_id');
    }
}
