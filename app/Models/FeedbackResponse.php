<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FeedbackResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackResponse extends Model
{
    /** @use HasFactory<FeedbackResponseFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'feedback_request_id',
        'feedback_question_id',
        'rating',
        'text_response',
        'reviewer_employee_id',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'feedback_request_id' => 'integer',
            'feedback_question_id' => 'integer',
            'rating' => 'decimal:2',
            'reviewer_employee_id' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FeedbackRequest::class, 'feedback_request_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestion::class, 'feedback_question_id');
    }

    public function reviewerEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }
}
