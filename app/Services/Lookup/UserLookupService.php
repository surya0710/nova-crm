<?php

namespace App\Services\Lookup;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'users';
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function organizationColumn(): string
    {
        return 'organizations.id';
    }

    protected function activeOnly(): bool
    {
        return true;
    }

    protected function activeColumn(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * @return list<string>
     */
    protected function eagerLoad(): array
    {
        return [];
    }

    protected function orderByColumn(): string
    {
        return 'name';
    }

    protected function baseQuery(Organization $organization): Builder
    {
        return User::query()
            ->whereHas('organizations', fn (Builder $q) => $q->where('organizations.id', $organization->id))
            ->where(function (Builder $q) {
                $q->whereNull('account_status')
                    ->orWhere('account_status', '!=', 'disabled');
            })
            ->with([
                'employees' => fn ($q) => $q
                    ->where('organization_id', $organization->id)
                    ->with(['department', 'designation']),
            ]);
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var User $model */
        $role = $model->getRoleNameInOrganization($organization);
        $employee = $model->employees->firstWhere('organization_id', $organization->id);

        $subtitle = $model->email;
        $badge = $role;
        $metadata = [
            'email' => $model->email,
            'role' => $role,
        ];

        if ($employee instanceof Employee) {
            $metadata['employee_id'] = $employee->id;
            $metadata['employee_code'] = $employee->employee_code;
            $metadata['department'] = $employee->department?->name;
            $metadata['designation'] = $employee->designation?->name;

            if ($employee->designation?->name) {
                $subtitle = $employee->designation->name;
            }
        }

        return new LookupResult(
            id: $model->id,
            label: $model->name,
            subtitle: $subtitle,
            badge: $badge,
            metadata: $metadata,
        );
    }

    public function findOne(Organization $organization, int|string $id): ?LookupResult
    {
        $model = $this->baseQuery($organization)->whereKey($id)->first();

        return $model ? $this->toResult($model, $organization) : null;
    }
}
