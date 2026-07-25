<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\Branch;
use App\Models\BulkOperation;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeAssignOrgUnitBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function __construct(
        protected string $field,
        protected string $actionKey,
        protected string $actionLabel,
        protected string $modelClass,
    ) {}

    public function key(): string
    {
        return $this->actionKey;
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
        return $this->actionLabel;
    }

    public function permission(): string
    {
        return 'hrms.update';
    }

    public function confirmationMessage(): string
    {
        return 'Assign the selected employees to the chosen '.$this->actionLabel.'?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [
            [
                'key' => $this->field,
                'label' => $this->actionLabel,
                'type' => 'integer',
                'required' => true,
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
        $id = (int) ($input[$this->field] ?? 0);
        $exists = $this->modelClass::query()
            ->where('organization_id', $operation->organization_id)
            ->whereKey($id)
            ->exists();

        if (! $exists) {
            return $this->failed($this->actionLabel.' was not found in this organization.');
        }

        if ((int) $record->{$this->field} === $id) {
            return $this->skipped('Already assigned.');
        }

        $record->forceFill([$this->field => $id])->save();

        return $this->success();
    }

    public static function department(): self
    {
        return new self('department_id', 'employee.assign_department', 'Department', Department::class);
    }

    public static function designation(): self
    {
        return new self('designation_id', 'employee.assign_designation', 'Designation', Designation::class);
    }

    public static function branch(): self
    {
        return new self('branch_id', 'employee.assign_branch', 'Branch', Branch::class);
    }
}
