<?php

namespace App\Services\Platform;

use App\Models\PlatformUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformUserService
{
    public function __construct(protected PlatformAuditService $audit) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return PlatformUser::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, PlatformUser $actor): PlatformUser
    {
        $user = PlatformUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);

        $this->audit->log('platform_user.created', $actor, null, [
            'platform_user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return $user;
    }

    public function update(PlatformUser $user, array $data, PlatformUser $actor): PlatformUser
    {
        $changes = [];

        if (isset($data['name'])) {
            $changes['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $changes['email'] = $data['email'];
        }

        if (isset($data['role'])) {
            $changes['role'] = $data['role'];
        }

        if (isset($data['status'])) {
            $changes['status'] = $data['status'];
        }

        if (! empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
        }

        $user->update($changes);

        $this->audit->log('platform_user.updated', $actor, null, [
            'platform_user_id' => $user->id,
            'changes' => array_keys($changes),
        ]);

        return $user->fresh();
    }

    public function recordLogin(PlatformUser $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    public function assertCanManage(PlatformUser $actor, ?PlatformUser $target = null): void
    {
        if (! $actor->hasPermission('platform.users.manage')) {
            throw ValidationException::withMessages([
                'authorization' => __('You are not allowed to manage platform users.'),
            ]);
        }

        if ($target?->isPlatformOwner() && ! $actor->isPlatformOwner()) {
            throw ValidationException::withMessages([
                'authorization' => __('Only platform owners can manage platform owner accounts.'),
            ]);
        }
    }
}
