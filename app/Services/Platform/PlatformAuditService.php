<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PlatformAuditService
{
    public function log(
        string $event,
        ?PlatformUser $actor = null,
        ?Organization $organization = null,
        array $properties = [],
        ?string $subject = null,
        ?Request $request = null,
    ): PlatformAuditLog {
        return PlatformAuditLog::create([
            'platform_user_id' => $actor?->id,
            'organization_id' => $organization?->id,
            'event' => $event,
            'subject' => $subject,
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
        ]);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PlatformAuditLog::query()
            ->with(['platformUser:id,name,email', 'organization:id,name'])
            ->latest();

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
