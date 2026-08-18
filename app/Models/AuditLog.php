<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'subject',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            'status_changed' => __('Status changed'),
            'assigned' => __('Assigned'),
            'issued' => __('Issued'),
            'cancelled' => __('Cancelled'),
            'converted' => __('Converted'),
            'accepted' => __('Accepted'),
            'rejected' => __('Rejected'),
            'expired' => __('Expired'),
            'sent' => __('Sent'),
            'created_from_quotation' => __('Created from quotation'),
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
