<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class PlatformGlobalUserService
{
    public function __construct(
        protected PlatformAuditService $audit,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['organizations' => fn ($q) => $q->select('organizations.id', 'organizations.name')])
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['organization_id'])) {
            $query->whereHas('organizations', fn ($q) => $q->where('organizations.id', $filters['organization_id']));
        }

        if (($filters['locked'] ?? null) === '1' && Schema::hasColumn('users', 'locked_at')) {
            $query->whereNotNull('locked_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function loginHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::withoutGlobalScopes()
            ->with(['user:id,name,email', 'organization:id,name'])
            ->where(function ($q) {
                $q->where('event', 'like', '%login%')
                    ->orWhere('event', 'like', '%auth%');
            })
            ->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function activeSessions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->whereNotNull('sessions.user_id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name',
                'users.email',
            ])
            ->orderByDesc('sessions.last_activity');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('sessions.ip_address', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function mfaStatus(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query()->orderBy('name');

        if (Schema::hasColumn('users', 'two_factor_secret')) {
            if (($filters['mfa'] ?? null) === 'enabled') {
                $query->whereNotNull('two_factor_secret');
            } elseif (($filters['mfa'] ?? null) === 'disabled') {
                $query->whereNull('two_factor_secret');
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function lock(User $user, PlatformUser $actor): User
    {
        if (Schema::hasColumn('users', 'locked_at')) {
            $user->forceFill(['locked_at' => now()])->save();
        }

        $this->audit->log('tenant_user.locked', $actor, null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user->fresh();
    }

    public function unlock(User $user, PlatformUser $actor): User
    {
        if (Schema::hasColumn('users', 'locked_at')) {
            $user->forceFill(['locked_at' => null])->save();
        }

        $this->audit->log('tenant_user.unlocked', $actor, null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user->fresh();
    }

    public function sendPasswordReset(User $user, PlatformUser $actor): string
    {
        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        $this->audit->log('tenant_user.password_reset_sent', $actor, null, [
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => $status,
        ]);

        return $status;
    }

    public function revokeSession(string $sessionId, PlatformUser $actor): void
    {
        DB::table('sessions')->where('id', $sessionId)->delete();

        $this->audit->log('tenant_session.revoked', $actor, null, [
            'session_id' => $sessionId,
        ]);
    }
}
