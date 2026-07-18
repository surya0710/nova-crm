<?php

namespace App\Models;

use Database\Factories\MarketingTouchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingTouch extends Model
{
    /** @use HasFactory<MarketingTouchFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'occurred_at',
        'channel',
        'source',
        'medium',
        'campaign',
        'content',
        'term',
        'gclid',
        'fbclid',
        'msclkid',
        'landing_page',
        'referrer',
        'referrer_host',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MarketingSession::class, 'session_id');
    }
}
