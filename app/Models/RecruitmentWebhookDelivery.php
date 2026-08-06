<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentWebhookDelivery extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_webhook_endpoint_id',
        'event_key',
        'status',
        'attempt_count',
        'http_status',
        'payload',
        'response_body',
        'last_error',
        'next_retry_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempt_count' => 'integer',
            'http_status' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(RecruitmentWebhookEndpoint::class, 'recruitment_webhook_endpoint_id');
    }
}
