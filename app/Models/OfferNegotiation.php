<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\OfferNegotiationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferNegotiation extends Model
{
    /** @use HasFactory<OfferNegotiationFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'offer_letter_id',
        'requested_salary',
        'requested_joining_date',
        'candidate_comments',
        'recruiter_notes',
        'outcome',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_salary' => 'decimal:2',
            'requested_joining_date' => 'date',
        ];
    }

    public function offerLetter(): BelongsTo
    {
        return $this->belongsTo(OfferLetter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function outcomeLabel(): string
    {
        return config('hrms.recruitment.negotiation_outcomes.'.$this->outcome, $this->outcome ?? '—');
    }
}
