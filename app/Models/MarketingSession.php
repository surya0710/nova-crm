<?php

namespace App\Models;

use Database\Factories\MarketingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSession extends Model
{
    /** @use HasFactory<MarketingSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'session_uuid',
        'started_at',
        'ended_at',
        'last_activity_at',
        'landing_page',
        'referrer',
        'user_agent',
        'ip_address',
        'device_type',
        'browser',
        'operating_system',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(MarketingVisitor::class, 'visitor_id');
    }

    /**
     * Named touchpoints() because Eloquent reserves Model::touches().
     */
    public function touchpoints(): HasMany
    {
        return $this->hasMany(MarketingTouch::class, 'session_id');
    }
}
