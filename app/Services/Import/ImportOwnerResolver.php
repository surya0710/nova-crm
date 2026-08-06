<?php

namespace App\Services\Import;

use App\Enums\UserAccountStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shared owner resolution for entity import adapters.
 *
 * Matching order is configurable through import.owner_resolution_priority.
 * Every match is checked for active tenant membership and account/employee status.
 */
class ImportOwnerResolver
{
    /**
     * Organization members that can be referenced in an Owner column.
     *
     * @return Collection<int, User>
     */
    public function listMembers(Organization $organization): Collection
    {
        return $organization->users()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('users.account_status')
                    ->orWhere('users.account_status', UserAccountStatus::Active->value);
            })
            ->orderBy('name')
            ->get();
    }

    public function resolve(Organization $organization, string $value): ?User
    {
        return $this->resolveWithDiagnostics($organization, $value)['user'];
    }

    /**
     * @return array{user: User|null, matched_by: string|null, error: string|null}
     */
    public function resolveWithDiagnostics(Organization $organization, string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return $this->result(error: 'Owner value is empty.');
        }

        $priority = config('import.owner_resolution_priority', [
            'user_id',
            'email',
            'employee_code',
            'user_name',
            'employee_name',
        ]);

        foreach ($priority as $matcher) {
            $matches = $this->matchesFor($organization, (string) $matcher, $value);
            if ($matches->isEmpty()) {
                continue;
            }

            if ($matches->count() > 1) {
                return $this->result(error: "Owner is ambiguous when matched by {$matcher}.");
            }

            /** @var User $user */
            $user = $matches->first();
            $eligibilityError = $this->eligibilityError($organization, $user);
            if ($eligibilityError !== null) {
                return $this->result(error: $eligibilityError, matchedBy: (string) $matcher);
            }

            return $this->result($user, (string) $matcher);
        }

        if ($this->matchesOutsideOrganization($organization, $value)) {
            return $this->result(error: 'Owner belongs to another organization.');
        }

        return $this->result(error: 'Unknown owner. Use user ID, email, employee code, or full name.');
    }

    /**
     * @return Collection<int, User>
     */
    protected function matchesFor(Organization $organization, string $matcher, string $value): Collection
    {
        $members = $organization->users();
        $needle = mb_strtolower($value);

        return match ($matcher) {
            'user_id' => ctype_digit($value)
                ? $members->whereKey((int) $value)->get()
                : collect(),
            'email' => $members
                ->whereRaw('LOWER(users.email) = ?', [$needle])
                ->get(),
            'user_name' => $members
                ->whereRaw('LOWER(users.name) = ?', [$needle])
                ->get(),
            'employee_code' => $this->usersFromEmployees(
                $organization,
                fn (Employee $employee): bool => mb_strtolower((string) $employee->employee_code) === $needle,
            ),
            'employee_name' => $this->usersFromEmployees(
                $organization,
                fn (Employee $employee): bool => mb_strtolower($employee->full_name) === $needle,
            ),
            default => collect(),
        };
    }

    /**
     * @param  callable(Employee): bool  $matches
     * @return Collection<int, User>
     */
    protected function usersFromEmployees(Organization $organization, callable $matches): Collection
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->filter($matches)
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function eligibilityError(Organization $organization, User $user): ?string
    {
        $membership = $organization->users()
            ->whereKey($user->id)
            ->first()?->pivot;

        if (! $membership || ! $membership->is_active) {
            return 'Owner is not an active organization member.';
        }

        $accountStatus = $user->account_status ?? UserAccountStatus::Active;
        if ($accountStatus !== UserAccountStatus::Active) {
            return 'Owner account is inactive or locked.';
        }

        $employees = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->get();

        if ($employees->isNotEmpty()) {
            $activeStatuses = config('import.owner_active_employee_statuses', [
                'active',
                'probation',
                'notice_period',
            ]);

            if (! $employees->contains(fn (Employee $employee): bool => in_array($employee->status, $activeStatuses, true))) {
                return 'Owner employee record is inactive.';
            }
        }

        return null;
    }

    protected function matchesOutsideOrganization(Organization $organization, string $value): bool
    {
        $needle = mb_strtolower($value);
        $excludedUserIds = $organization->users()->pluck('users.id');

        if (ctype_digit($value) && User::query()->whereKey((int) $value)->whereNotIn('id', $excludedUserIds)->exists()) {
            return true;
        }

        if (User::query()
            ->whereNotIn('id', $excludedUserIds)
            ->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(email) = ?', [$needle])
                    ->orWhereRaw('LOWER(name) = ?', [$needle]);
            })
            ->exists()) {
            return true;
        }

        return Employee::query()
            ->withoutGlobalScopes()
            ->where('organization_id', '!=', $organization->id)
            ->whereNotNull('user_id')
            ->whereNull('deleted_at')
            ->get()
            ->contains(fn (Employee $employee): bool => mb_strtolower((string) $employee->employee_code) === $needle
                || mb_strtolower($employee->full_name) === $needle);
    }

    /**
     * @return array{user: User|null, matched_by: string|null, error: string|null}
     */
    protected function result(?User $user = null, ?string $matchedBy = null, ?string $error = null): array
    {
        return [
            'user' => $user,
            'matched_by' => $matchedBy,
            'error' => $error,
        ];
    }
}
