<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Enums\UserAccountStatus;
use App\Models\BulkOperation;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use App\Services\Identity\UserAccountService;
use App\Services\Identity\UserInvitationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserAccountBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function __construct(
        protected UserAccountService $accounts,
        protected UserInvitationService $invitations,
        protected string $mode,
    ) {}

    public function key(): string
    {
        return 'user.'.$this->mode;
    }

    public function module(): string
    {
        return 'administration';
    }

    public function entityType(): string
    {
        return 'user';
    }

    public function label(): string
    {
        return match ($this->mode) {
            'activate' => 'Activate Users',
            'disable' => 'Disable Users',
            'lock' => 'Lock Users',
            'unlock' => 'Unlock Users',
            'resend_invitation' => 'Resend Invitations',
            default => 'User Action',
        };
    }

    public function permission(): string
    {
        return match ($this->mode) {
            'resend_invitation' => 'users.manage',
            default => 'users.update',
        };
    }

    public function confirmationMessage(): string
    {
        return $this->label().' for the selected users?';
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
        return $this->baseOrganizationQuery(User::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var User $record */
        $organization = Organization::query()->findOrFail($operation->organization_id);
        $actor = User::query()->findOrFail($operation->initiated_by);

        if ((int) $record->id === (int) $actor->id && in_array($this->mode, ['disable', 'lock'], true)) {
            return $this->skipped('You cannot disable or lock your own account.');
        }

        return match ($this->mode) {
            'activate' => $this->activate($record),
            'disable' => $this->disable($record),
            'lock' => $this->doLock($record, $organization, $actor),
            'unlock' => $this->doUnlock($record, $organization, $actor),
            'resend_invitation' => $this->resend($record, $organization, $actor),
            default => $this->failed('Unknown action.'),
        };
    }

    protected function activate(User $user): array
    {
        $status = $user->account_status instanceof UserAccountStatus
            ? $user->account_status
            : UserAccountStatus::tryFrom((string) $user->account_status);

        if ($status === UserAccountStatus::Active) {
            return $this->skipped('Already active.');
        }

        $user->forceFill([
            'account_status' => UserAccountStatus::Active,
            'locked_at' => null,
        ])->save();

        return $this->success();
    }

    protected function disable(User $user): array
    {
        $user->forceFill([
            'account_status' => UserAccountStatus::Disabled,
        ])->save();

        return $this->success();
    }

    protected function doLock(User $user, Organization $organization, User $actor): array
    {
        $this->accounts->lock($user, $organization, $actor);

        return $this->success();
    }

    protected function doUnlock(User $user, Organization $organization, User $actor): array
    {
        $this->accounts->unlock($user, $organization, $actor);

        return $this->success();
    }

    protected function resend(User $user, Organization $organization, User $actor): array
    {
        $this->invitations->resend($user, $organization, $actor);

        return $this->success();
    }
}
