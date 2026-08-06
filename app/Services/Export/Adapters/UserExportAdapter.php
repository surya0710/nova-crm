<?php

namespace App\Services\Export\Adapters;

use App\Models\User;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class UserExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'user';
    }

    public function entityLabel(): string
    {
        return 'Users';
    }

    public function module(): string
    {
        return 'administration';
    }

    public function permission(): string
    {
        return 'users.view';
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('email', 'Email'),
            new ExportColumnDefinition('account_status', 'Account Status', ExportColumnDefinition::TYPE_COMPUTED),
            new ExportColumnDefinition('invitation_status', 'Invitation Status', ExportColumnDefinition::TYPE_COMPUTED, default: false, eager: ['latestInvitation']),
            new ExportColumnDefinition('portal_access_enabled', 'Portal Access', ExportColumnDefinition::TYPE_BOOLEAN, default: false),
            new ExportColumnDefinition('last_login_at', 'Last Login', ExportColumnDefinition::TYPE_DATETIME),
            new ExportColumnDefinition('login_count', 'Login Count', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('password', 'Password', sensitive: true, default: false, hidden: true),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var User $record */
        return [
            'account_status' => $record->displayAccountStatusLabel(),
            'invitation_status' => $record->latestInvitation
                ? ($record->latestInvitation->accepted_at ? 'accepted' : ($record->latestInvitation->isExpired() ? 'expired' : 'pending'))
                : 'none',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if ($search = Arr::get($filters, 'search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = Arr::get($filters, 'account_status')) {
            $query->where('account_status', $status);
        }
    }
}
