<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\GoalTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalTemplate extends Model
{
    /** @use HasFactory<GoalTemplateFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'goal_category_id',
        'title',
        'description',
        'goal_type',
        'default_weight',
        'measurement_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'goal_category_id' => 'integer',
            'default_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'goal_template_id');
    }
}
