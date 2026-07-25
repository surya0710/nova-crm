<?php

namespace App\Services\Import\Adapters\Concerns;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Carbon;

/**
 * Shared helpers for organization-scoped HRMS import adapters.
 */
trait OrganizationImportSupport
{
    protected function organizationFor(ImportSession $session): Organization
    {
        $organization = Organization::query()->find($session->organization_id);

        if (! $organization) {
            throw new \RuntimeException('Import session organization was not found.');
        }

        if ($this->tenant->id() !== $organization->id) {
            $this->tenant->set($organization);
        }

        return $organization;
    }

    protected function actorFor(ImportSession $session): User
    {
        if ($session->uploaded_by) {
            $user = User::query()->find($session->uploaded_by);
            if ($user) {
                return $user;
            }
        }

        $authUser = auth()->user();
        if ($authUser instanceof User) {
            return $authUser;
        }

        throw new \RuntimeException('Import session has no uploading user.');
    }

    protected function duplicateStrategy(ImportSession $session): string
    {
        $strategy = strtolower(trim((string) ($session->metadata['duplicate_strategy'] ?? 'skip')));

        return in_array($strategy, ['skip', 'update', 'create'], true) ? $strategy : 'skip';
    }

    protected function shouldReportDatabaseDuplicates(ImportSession $session): bool
    {
        return $this->duplicateStrategy($session) === 'skip';
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function normalizeEmail(mixed $email): ?string
    {
        $email = $this->stringOrNull($email);

        return $email !== null ? strtolower($email) : null;
    }

    protected function parseBoolean(mixed $value, ?bool $default = null): ?bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        return $default;
    }

    protected function parseInteger(mixed $value): ?int
    {
        $string = $this->stringOrNull($value);

        if ($string === null || ! preg_match('/^-?\d+$/', $string)) {
            return null;
        }

        return (int) $string;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            return null;
        }

        try {
            return Carbon::parse($string)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function resolveConfigKey(array $map, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $needle = strtolower(trim($value));

        foreach ($map as $key => $label) {
            if (strtolower((string) $key) === $needle || strtolower((string) $label) === $needle) {
                return (string) $key;
            }
        }

        return null;
    }

    protected function resolveBranchByCode(Organization $organization, ?string $code): ?Branch
    {
        if ($code === null) {
            return null;
        }

        return Branch::query()
            ->where('organization_id', $organization->id)
            ->where('code', $code)
            ->first();
    }

    protected function resolveDepartmentByCode(Organization $organization, ?string $code): ?Department
    {
        if ($code === null) {
            return null;
        }

        return Department::query()
            ->where('organization_id', $organization->id)
            ->where('code', $code)
            ->first();
    }

    protected function resolveDesignationByCode(Organization $organization, ?string $code): ?Designation
    {
        if ($code === null) {
            return null;
        }

        return Designation::query()
            ->where('organization_id', $organization->id)
            ->where('code', $code)
            ->first();
    }

    /**
     * @return array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}
     */
    protected function error(int $rowNumber, ?string $field, string $message, mixed $value): array
    {
        return [
            'row_number' => $rowNumber,
            'column' => null,
            'field' => $field,
            'error' => $message,
            'value' => $value !== null ? (string) $value : null,
        ];
    }
}
