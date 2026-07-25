<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\User;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\OrganizationMemberService;
use App\Services\TenantContext;

class UserImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(
        protected TenantContext $tenant,
        protected OrganizationMemberService $members,
    ) {}

    public function entityType(): string
    {
        return 'user';
    }

    public function entityLabel(): string
    {
        return 'User';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'name', label: 'Full Name', required: true, aliases: ['Name', 'User Name']),
            new ImportFieldDefinition(key: 'email', label: 'Email', required: true, dataType: ImportFieldDefinition::TYPE_EMAIL, aliases: ['Work Email']),
            new ImportFieldDefinition(key: 'role', label: 'Role', required: true, aliases: ['Role Slug', 'Role Name']),
            new ImportFieldDefinition(key: 'send_invitation', label: 'Send Invitation', required: false, dataType: ImportFieldDefinition::TYPE_BOOLEAN, aliases: ['Invite']),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seen = [];
        $assignable = OrganizationMemberService::assignableRoleSlugs();

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }

            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $email = $this->normalizeEmail($values['email'] ?? null);
            $role = $this->stringOrNull($values['role'] ?? null);

            if ($email === null) {
                $errors[] = $this->error($rowNumber, 'email', 'Email is required.', null);
                continue;
            }

            if (isset($seen[$email])) {
                $errors[] = $this->error($rowNumber, 'email', 'Duplicate email in file.', $email);
            }
            $seen[$email] = true;

            if ($role === null || ! in_array(strtolower($role), array_map('strtolower', $assignable), true)) {
                // Also accept role labels loosely
                $matched = $this->resolveConfigKey(
                    collect(config('rbac.roles', []))->mapWithKeys(fn ($def, $slug) => [$slug => $def['name'] ?? $slug])->all(),
                    $role
                );
                if ($matched === null || $matched === 'organization-owner') {
                    $errors[] = $this->error($rowNumber, 'role', 'Role is invalid or not assignable.', $role);
                }
            }

            if ($this->shouldReportDatabaseDuplicates($session)
                && $organization->users()->where('users.email', $email)->exists()) {
                $errors[] = $this->error($rowNumber, 'email', 'Duplicate user already exists in organization.', $email);
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $actor = $this->actorFor($session);
        $strategy = $this->duplicateStrategy($session);
        $email = $this->normalizeEmail($mappedRow['email'] ?? null);
        $name = $this->stringOrNull($mappedRow['name'] ?? null) ?? $email;
        $roleRaw = $this->stringOrNull($mappedRow['role'] ?? null);
        $role = $this->resolveConfigKey(
            collect(config('rbac.roles', []))->mapWithKeys(fn ($def, $slug) => [$slug => $def['name'] ?? $slug])->all(),
            $roleRaw
        ) ?? strtolower((string) $roleRaw);
        $sendInvitation = $this->parseBoolean($mappedRow['send_invitation'] ?? null, true);

        $existing = User::query()->where('email', $email)->first();
        if ($existing && $organization->users()->where('users.id', $existing->id)->exists()) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }
            if ($strategy === 'update') {
                $this->members->updateMemberRole($organization, $existing, $role);

                return ['action' => 'updated', 'id' => $existing->id];
            }
        }

        $user = $this->members->addMember($organization, [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'send_invitation' => $sendInvitation,
            'notify' => $sendInvitation,
        ], $actor);

        return ['action' => 'created', 'id' => $user->id];
    }
}
