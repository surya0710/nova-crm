<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use App\Services\Hrms\EmployeeProvisioningService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeGenerateLoginBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function __construct(
        protected EmployeeProvisioningService $provisioning,
    ) {}

    public function key(): string
    {
        return 'employee.generate_login';
    }

    public function module(): string
    {
        return 'hrms';
    }

    public function entityType(): string
    {
        return 'employee';
    }

    public function label(): string
    {
        return 'Generate Login Accounts';
    }

    public function permission(): string
    {
        return 'hrms.manage';
    }

    public function confirmationMessage(): string
    {
        return 'Generate login accounts and send invitations for selected employees without accounts?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        $roles = collect(config('rbac.roles', []))
            ->except('organization-owner')
            ->mapWithKeys(fn ($def, $slug) => [$slug => $def['name'] ?? $slug])
            ->all();

        return [
            [
                'key' => 'role',
                'label' => 'Role',
                'type' => 'select',
                'required' => true,
                'options' => $roles,
            ],
            [
                'key' => 'send_invitation',
                'label' => 'Send Invitation',
                'type' => 'boolean',
                'required' => false,
            ],
            [
                'key' => 'portal_access',
                'label' => 'Portal Access',
                'type' => 'boolean',
                'required' => false,
            ],
        ];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery(Employee::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Employee $record */
        if ($record->user_id) {
            return $this->skipped('Employee already has a login account.');
        }

        if (! $record->email) {
            return $this->failed('Employee email is required to create a login.');
        }

        $actor = User::query()->findOrFail($operation->initiated_by);

        $this->provisioning->provisionUserForEmployee($record, [
            'email' => $record->email,
            'name' => $record->full_name,
            'role' => $input['role'] ?? config('identity.default_employee_role', 'employee'),
            'send_invitation' => filter_var($input['send_invitation'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'portal_access' => filter_var($input['portal_access'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'notify' => false,
        ], $actor);

        return $this->success();
    }
}
