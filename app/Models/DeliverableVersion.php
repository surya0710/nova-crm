<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableVersion extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'deliverable_id',
        'version_number',
        'label',
        'notes',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by_user_id',
        'uploaded_by_client_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'size' => 'integer',
        ];
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function uploadedByClient(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'uploaded_by_client_user_id');
    }
}
