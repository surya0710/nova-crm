<?php

namespace App\Services\Platform;

use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\DB;

class PlatformSecurityService
{
    public function __construct(
        protected PlatformConfigurationService $configuration,
        protected PlatformAuditService $audit,
    ) {}

    public function overview(): array
    {
        $policies = $this->policies();

        return [
            'policies' => $policies,
            'platform_users_mfa_ready' => PlatformUser::query()->where('two_factor_ready', true)->count(),
            'platform_users_locked' => PlatformUser::query()->whereNotNull('locked_at')->count(),
            'active_sessions' => DB::table('sessions')->whereNotNull('user_id')->count(),
            'api_tokens' => DB::table('personal_access_tokens')->count(),
            'recent_security_events' => $this->securityEvents(10),
        ];
    }

    public function policies(): array
    {
        $defaults = config('platform.security_defaults', []);
        $stored = $this->configuration->get('security', 'policies', []);

        return array_replace_recursive($defaults, is_array($stored) ? $stored : []);
    }

    public function updatePolicies(array $data, PlatformUser $actor): array
    {
        $policies = array_replace_recursive($this->policies(), $data);
        $this->configuration->set('security', 'policies', $policies, $actor);

        $this->audit->log('security.policies_updated', $actor, null, [
            'policies' => $policies,
        ]);

        return $policies;
    }

    public function securityEvents(int $limit = 50): array
    {
        return PlatformAuditLog::query()
            ->with(['platformUser:id,name', 'organization:id,name'])
            ->where(function ($q) {
                $q->where('event', 'like', '%security%')
                    ->orWhere('event', 'like', '%login%')
                    ->orWhere('event', 'like', '%lock%')
                    ->orWhere('event', 'like', '%impersonat%')
                    ->orWhere('event', 'like', '%password%')
                    ->orWhere('event', 'like', '%session%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PlatformAuditLog $log) => [
                'event' => $log->event,
                'subject' => $log->subject,
                'user' => $log->platformUser?->name,
                'organization' => $log->organization?->name,
                'created_at' => $log->created_at,
            ])
            ->all();
    }

    public function lockPlatformUser(PlatformUser $user, PlatformUser $actor): PlatformUser
    {
        $user->update(['locked_at' => now(), 'status' => 'inactive']);

        $this->audit->log('platform_user.locked', $actor, null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user->fresh();
    }

    public function unlockPlatformUser(PlatformUser $user, PlatformUser $actor): PlatformUser
    {
        $user->update([
            'locked_at' => null,
            'failed_login_attempts' => 0,
            'status' => 'active',
        ]);

        $this->audit->log('platform_user.unlocked', $actor, null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user->fresh();
    }
}
