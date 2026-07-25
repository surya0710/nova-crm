<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationQuickAction extends Model
{
    use Auditable;
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'quick_action_id',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function quickAction(): BelongsTo
    {
        return $this->belongsTo(DashboardQuickAction::class, 'quick_action_id');
    }
}
