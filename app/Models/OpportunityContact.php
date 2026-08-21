<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityContact extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'opportunity_id',
        'contact_id',
        'role',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function getRoleLabelAttribute(): string
    {
        return config('pipeline.contact_roles.'.$this->role, ucfirst(str_replace('_', ' ', $this->role)));
    }
}
