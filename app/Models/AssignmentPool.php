<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AssignmentPoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentPool extends Model
{
    /** @use HasFactory<AssignmentPoolFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_active',
        'strategy',
        'rotation_position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rotation_position' => 'integer',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(AssignmentPoolMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('is_active', true)->orderBy('id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AssignmentRule::class);
    }
}
