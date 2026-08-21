<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmEmailWebhookEvent extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'endpoint_id',
        'crm_email_message_id',
        'provider',
        'provider_event_id',
        'event',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(CrmEmailWebhookEndpoint::class, 'endpoint_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CrmEmailMessage::class, 'crm_email_message_id');
    }
}
