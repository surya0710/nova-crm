<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CandidatePortalSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatePortalSetting extends Model
{
    /** @use HasFactory<CandidatePortalSettingFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'portal_enabled',
        'allow_guest_apply',
        'require_login_to_apply',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'portal_enabled' => 'boolean',
            'allow_guest_apply' => 'boolean',
            'require_login_to_apply' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
