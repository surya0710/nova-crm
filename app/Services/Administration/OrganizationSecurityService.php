<?php

namespace App\Services\Administration;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class OrganizationSecurityService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'password_min_length' => 8,
            'password_require_special' => false,
            'password_require_number' => true,
            'password_require_uppercase' => true,
            'mfa_required' => false,
            'session_lifetime_minutes' => 120,
            'max_concurrent_sessions' => 5,
            'trusted_devices_enabled' => false,
            'api_token_expiry_days' => 365,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function policies(Organization $organization): array
    {
        $stored = is_array($organization->settings['security'] ?? null)
            ? $organization->settings['security']
            : [];

        return array_merge($this->defaults(), $stored);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updatePolicies(Organization $organization, array $input, User $actor): array
    {
        $policies = array_merge($this->policies($organization), [
            'password_min_length' => (int) ($input['password_min_length'] ?? 8),
            'password_require_special' => (bool) ($input['password_require_special'] ?? false),
            'password_require_number' => (bool) ($input['password_require_number'] ?? true),
            'password_require_uppercase' => (bool) ($input['password_require_uppercase'] ?? true),
            'mfa_required' => (bool) ($input['mfa_required'] ?? false),
            'session_lifetime_minutes' => (int) ($input['session_lifetime_minutes'] ?? 120),
            'max_concurrent_sessions' => (int) ($input['max_concurrent_sessions'] ?? 5),
            'trusted_devices_enabled' => (bool) ($input['trusted_devices_enabled'] ?? false),
            'api_token_expiry_days' => (int) ($input['api_token_expiry_days'] ?? 365),
            'updated_by' => $actor->id,
            'updated_at' => now()->toIso8601String(),
        ]);

        $settings = $organization->settings ?? [];
        $settings['security'] = $policies;
        $organization->update(['settings' => $settings]);

        return $policies;
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(?Organization $organization): array
    {
        if (! $organization) {
            return array_merge($this->defaults(), [
                'recent_security_events' => 0,
                'status' => 'unknown',
            ]);
        }

        $policies = $this->policies($organization);
        $events = 0;

        if (Schema::hasTable('audit_logs')) {
            $events = AuditLog::query()
                ->where(function ($q) {
                    $q->where('event', 'like', '%login%')
                        ->orWhere('event', 'like', '%security%')
                        ->orWhere('event', 'like', '%password%')
                        ->orWhere('event', 'like', '%mfa%')
                        ->orWhere('subject', 'like', '%security%');
                })
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }

        $score = 0;
        if ($policies['mfa_required']) {
            $score += 2;
        }
        if (($policies['password_min_length'] ?? 8) >= 10) {
            $score += 1;
        }
        if ($policies['password_require_special']) {
            $score += 1;
        }
        if ($policies['trusted_devices_enabled']) {
            $score += 1;
        }

        $status = match (true) {
            $score >= 4 => 'strong',
            $score >= 2 => 'moderate',
            default => 'needs_attention',
        };

        return array_merge($policies, [
            'recent_security_events' => $events,
            'status' => $status,
            'score' => $score,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, AuditLog>
     */
    public function loginHistory(Organization $organization, int $limit = 25)
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::query()
            ->with('user')
            ->where(function ($q) {
                $q->where('event', 'like', '%login%')
                    ->orWhere('event', 'like', '%logout%')
                    ->orWhere('event', 'like', '%session%');
            })
            ->latest()
            ->limit($limit)
            ->get();
    }
}
