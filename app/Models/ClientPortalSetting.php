<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class ClientPortalSetting extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'portal_enabled',
        'welcome_message',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'portal_enabled' => 'boolean',
            'settings' => 'array',
        ];
    }
}
