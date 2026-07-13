<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFilter extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'name',
        'description',
        'filter_definition',
        'visibility',
        'validation_status',
        'validation_errors',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'filter_definition' => 'array',
            'validation_errors' => 'array',
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

    public function isShared(): bool
    {
        return $this->visibility === 'shared';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }
}
