<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmEmailMessage extends Model
{
    use Auditable, BelongsToOrganization;

    public const STATUSES = ['queued', 'sending', 'sent', 'delivered', 'failed', 'bounced'];

    public const TERMINAL_STATUSES = ['delivered', 'failed', 'bounced'];

    protected $fillable = [
        'organization_id',
        'conversation_id',
        'related_type',
        'related_id',
        'customer_id',
        'contact_id',
        'template_id',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'attachments',
        'attachment_paths',
        'status',
        'provider',
        'provider_message_id',
        'rfc_message_id',
        'in_reply_to',
        'references_header',
        'thread_id',
        'mailable_class',
        'direction',
        'from_email',
        'from_name',
        'error_message',
        'queued_at',
        'sending_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'bounced_at',
        'bounce_type',
        'bounce_reason',
        'provider_metadata',
        'idempotency_key',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'attachments' => 'array',
            'attachment_paths' => 'array',
            'provider_metadata' => 'array',
            'queued_at' => 'datetime',
            'sending_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CrmEmailConversation::class, 'conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CrmEmailTemplate::class, 'template_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(CrmEmailWebhookEvent::class, 'crm_email_message_id');
    }

    public function statusLabel(): string
    {
        return config('crm_email.statuses.'.$this->status, ucfirst((string) $this->status));
    }

    public function isQueued(): bool
    {
        return in_array($this->status, ['queued', 'sending'], true);
    }

    public function flashKey(string $sentKey): string
    {
        return $this->isQueued()
            ? (string) preg_replace('/-sent$/', '-queued', $sentKey)
            : $sentKey;
    }

    public function wasSent(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'bounced'], true)
            || $this->sent_at !== null;
    }

    public function supportsDeliveryTracking(): bool
    {
        $provider = $this->provider ?: 'smtp';

        return (bool) (config('organization_mail.providers.'.$provider.'.delivery_tracking')
            ?? in_array($provider, config('crm_email.tracking_providers', []), true));
    }
}
