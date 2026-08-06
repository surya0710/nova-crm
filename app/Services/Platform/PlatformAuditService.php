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
            $query->where('event', 'like', '%'.$filters['event'].'%');
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            match ($filters['category']) {
                'security' => $query->where(function ($q) {
                    $q->where('event', 'like', '%security%')
                        ->orWhere('event', 'like', '%login%')
                        ->orWhere('event', 'like', '%lock%')
                        ->orWhere('event', 'like', '%password%')
                        ->orWhere('event', 'like', '%impersonat%');
                }),
                'organization' => $query->where('event', 'like', 'organization.%'),
                'administrative' => $query->where(function ($q) {
                    $q->where('event', 'like', 'platform.%')
                        ->orWhere('event', 'like', 'configuration.%')
                        ->orWhere('event', 'like', 'licensing.%')
                        ->orWhere('event', 'like', 'coupon.%')
                        ->orWhere('event', 'like', 'support.%');
                }),
                default => null,
            };
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
