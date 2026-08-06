<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderLeadImportRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manual lead-import run statistics (P7C.5).
 * Written exclusively through MarketingProviderService.
 */
class MarketingProviderLeadImportRun extends Model
{
    /** @use HasFactory<MarketingProviderLeadImportRunFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'triggered_by',
        'imported_count',
        'skipped_count',
        'failed_count',
        'status',
        'message',
        'metadata',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'imported_at' => 'datetime',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
