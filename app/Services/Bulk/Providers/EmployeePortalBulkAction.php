<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use App\Services\Identity\UserAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeePortalBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function __construct(
        protected UserAccountService $accounts,
        protected string $mode, // enable|disable|lock|unlock
    ) {}

    public function key(): string
    {
        return match ($this->mode) {
            'enable' => 'employee.enable_portal',
            'disable' => 'employee.disable_portal',
            'lock' => 'employee.lock_account',
            'unlock' => 'employee.unlock_account',
            default => 'employee.account_action',
        };
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
        return match ($this->mode) {
            'enable' => 'Enable Portal Access',
            'disable' => 'Disable Portal Access',
            'lock' => 'Lock Accounts',
            'unlock' => 'Unlock Accounts',
            default => 'Account Action',
        };
    }

    public function permission(): string
    {
        return 'hrms.manage';
    }

    public function confirmationMessage(): string
    {
        return $this->label().' for selected employees?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery(Employee::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Employee $record */
        if (! $record->user_id || ! $record->user) {
            return $this->skipped('Employee has no login account.');
        }

        $organization = Organization::query()->findOrFail($operation->organization_id);
        $actor = User::query()->findOrFail($operation->initiated_by);
        $user = $record->user;

        match ($this->mode) {
            'enable' => $this->accounts->enablePortal($user, $organization, $actor, false),
            'disable' => $this->accounts->disablePortal($user, $organization, $actor),
            'lock' => $this->accounts->lock($user, $organization, $actor),
            'unlock' => $this->accounts->unlock($user, $organization, $actor),
            default => null,
        };

        return $this->success();
    }

    public static function enablePortal(UserAccountService $accounts): self
    {
        return new self($accounts, 'enable');
    }

    public static function disablePortal(UserAccountService $accounts): self
    {
        return new self($accounts, 'disable');
    }

    public static function lock(UserAccountService $accounts): self
    {
        return new self($accounts, 'lock');
    }

    public static function unlock(UserAccountService $accounts): self
    {
        return new self($accounts, 'unlock');
    }
}
