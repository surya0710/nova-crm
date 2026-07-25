<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSupportTicket extends Model
{
    protected $fillable = [
        'organization_id',
        'platform_user_id',
        'assignee_id',
        'subject',
        'body',
        'status',
        'priority',
        'category',
        'requester_name',
        'requester_email',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assignee_id');
    }
}
