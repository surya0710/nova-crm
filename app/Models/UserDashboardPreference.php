<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardPreference extends Model
{
    use Auditable;
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'widget_id',
        'position_x',
        'position_y',
        'width',
        'height',
        'is_visible',
        'custom_configuration',
    ];

    protected function casts(): array
    {
        return [
            'position_x' => 'integer',
            'position_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_visible' => 'boolean',
            'custom_configuration' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id');
    }
}
