<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CommercialReminderDispatch extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'subject_type',
        'subject_id',
        'reminder_type',
        'dispatched_on',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_on' => 'date',
        ];
    }
}
