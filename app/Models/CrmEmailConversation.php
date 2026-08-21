<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmEmailConversation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'contact_id',
        'related_type',
        'related_id',
        'thread_id',
        'subject',
        'message_count',
        'last_status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmEmailMessage::class, 'conversation_id');
    }

    public function lastStatusLabel(): string
    {
        return config('crm_email.statuses.'.$this->last_status, ucfirst((string) $this->last_status));
    }
}
