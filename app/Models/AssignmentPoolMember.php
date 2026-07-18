<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AssignmentPoolMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentPoolMember extends Model
{
    /** @use HasFactory<AssignmentPoolMemberFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'assignment_pool_id',
        'user_id',
        'weight',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(AssignmentPool::class, 'assignment_pool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
