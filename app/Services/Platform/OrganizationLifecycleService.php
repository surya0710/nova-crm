<?php

namespace App\Services\Platform;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrganizationLifecycleService
{
    public function assertCanLogin(Organization $organization): void
    {
        if ($organization->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => __('Your organization has been suspended. Please contact support.'),
            ]);
        }

        if ($organization->isArchived()) {
            throw ValidationException::withMessages([
                'email' => __('Your organization has been archived and is no longer accessible.'),
            ]);
        }
    }

    public function assertApiAccess(?Organization $organization): void
    {
        if (! $organization) {
            return;
        }

        if ($organization->isSuspended()) {
            abort(403, 'API access is disabled for suspended organizations.');
        }

        if ($organization->isArchived()) {
            abort(403, 'API access is disabled for archived organizations.');
        }
    }

    public function isReadOnly(?Organization $organization): bool
    {
        return $organization?->isArchived() ?? false;
    }

    public function assertCanMutate(Request $request, TenantContext $tenant): void
    {
        $organization = $tenant->get();

        if (! $organization || ! $this->isReadOnly($organization)) {
            return;
        }

        if ($request->isMethodSafe()) {
            return;
        }

        abort(403, 'This organization is archived. Changes are not allowed.');
    }

    public function shouldProcessJobs(?int $organizationId): bool
    {
        if (! $organizationId) {
            return true;
        }

        $organization = Organization::query()->find($organizationId);

        if (! $organization) {
            return true;
        }

        return $organization->status === OrganizationStatus::Active;
    }
}
