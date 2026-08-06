<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProjectAccess extends Model
{
    use Auditable, BelongsToOrganization;

    protected $table = 'client_project_access';

    protected $fillable = [
        'organization_id',
        'client_user_id',
        'project_id',
        'scopes',
        'granted_by',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'granted_at' => 'datetime',
        ];
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function allows(string $scope): bool
    {
        $scopes = $this->scopes ?? config('portal.default_share_scopes', []);

        return in_array($scope, $scopes, true);
    }
}
