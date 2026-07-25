<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'body',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'broadcast',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'broadcast' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by');
    }
}
