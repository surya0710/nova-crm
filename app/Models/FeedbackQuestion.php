<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FeedbackQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackQuestion extends Model
{
    /** @use HasFactory<FeedbackQuestionFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'feedback_template_id',
        'question_type',
        'competency_id',
        'question_text',
        'help_text',
        'scale_min',
        'scale_max',
        'sort_order',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'feedback_template_id' => 'integer',
            'competency_id' => 'integer',
            'scale_min' => 'integer',
            'scale_max' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FeedbackTemplate::class, 'feedback_template_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class, 'feedback_question_id');
    }

    public function isRatingQuestion(): bool
    {
        return in_array($this->question_type, ['competency', 'rating', 'scale'], true);
    }
}
