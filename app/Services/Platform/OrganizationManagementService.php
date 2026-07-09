<?php

namespace App\Services\Platform;

use App\Enums\OrganizationStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\IndustryTemplateVersion;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrganizationManagementService
{
    public function __construct(
        protected PlatformAuditService $audit,
        protected PlatformDashboardService $dashboard,
        protected IndustryTemplateApplicationService $templateApplicationService,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Organization::query()
            ->withCount('users')
            ->with(['owners' => fn ($q) => $q->limit(1)]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function profile(Organization $organization): array
    {
        $organization->loadCount('users');

        $counts = [
            'leads' => Lead::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'customers' => Customer::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'opportunities' => Opportunity::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'invoices' => Invoice::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'revenue_managed' => (float) Payment::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->sum('amount'),
        ];

        $recentLogins = DB::table('sessions')
            ->join('organization_user', 'sessions.user_id', '=', 'organization_user.user_id')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('organization_user.organization_id', $organization->id)
            ->select('users.name', 'users.email', 'sessions.last_activity')
            ->orderByDesc('sessions.last_activity')
            ->limit(5)
            ->get();

        $recentAudit = AuditLog::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $apiTokens = DB::table('personal_access_tokens')
            ->join('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
            ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
            ->where('organization_user.organization_id', $organization->id)
            ->where('personal_access_tokens.tokenable_type', User::class)
            ->count();

        return [
            'organization' => $organization,
            'owner' => $organization->primaryOwner(),
            'counts' => $counts,
            'recent_logins' => $recentLogins,
            'recent_audit' => $recentAudit,
            'api_tokens' => $apiTokens,
            'template_application' => $organization->initialTemplateApplication(),
        ];
    }

    public function create(array $data, PlatformUser $actor): Organization
    {
        return DB::transaction(function () use ($data, $actor) {
            $version = null;

            if (! empty($data['template_version_id'])) {
                $version = IndustryTemplateVersion::query()
                    ->with('template')
                    ->findOrFail($data['template_version_id']);
            }

            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'plan' => $data['plan'],
                'status' => $data['status'],
                'is_active' => $data['status'] === 'active',
                'timezone' => $data['timezone'] ?? 'UTC',
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'tax_name' => $data['tax_name'] ?? null,
            ]);

            if (! empty($data['owner_email'])) {
                $owner = User::firstOrCreate(
                    ['email' => $data['owner_email']],
                    [
                        'name' => $data['owner_name'],
                        'password' => $data['owner_password'],
                    ]
                );

                if ($owner->wasRecentlyCreated) {
                    event(new Registered($owner));
                }

                $ownerRole = $organization->roles()->where('slug', 'organization-owner')->firstOrFail();

                $organization->users()->syncWithoutDetaching([
                    $owner->id => [
                        'role_id' => $ownerRole->id,
                        'role' => 'organization-owner',
                        'is_owner' => true,
                    ],
                ]);
            }

            $application = null;
            if ($version) {
                $application = $this->templateApplicationService->applyToNewOrganization(
                    $organization,
                    $version,
                    $actor,
                    array_intersect_key($data, array_flip(['timezone', 'currency', 'tax_name'])),
                );
            }

            $this->audit->log('organization.created', $actor, $organization, [
                'organization_name' => $organization->name,
                'plan' => $organization->plan,
                'status' => (string) $organization->status->value,
                'template_id' => $application?->industry_template_id,
                'template_version_id' => $application?->industry_template_version_id,
            ]);

            if ($application) {
                $this->audit->log('industry_template.applied_to_organization', $actor, $organization, [
                    'template_id' => $application->industry_template_id,
                    'template_version_id' => $application->industry_template_version_id,
                    'payload_hash' => $application->payload_hash,
                    'applied_sections' => $application->applied_sections,
                    'skipped_sections' => $application->skipped_sections,
                ]);
            }

            $this->dashboard->clearCache();

            return $organization->fresh();
        });
    }

    public function suspend(Organization $organization, PlatformUser $actor): Organization
    {
        $organization->update([
            'status' => OrganizationStatus::Suspended,
            'is_active' => false,
        ]);

        $this->audit->log('organization.suspended', $actor, $organization, [
            'organization_name' => $organization->name,
        ]);

        $this->dashboard->clearCache();

        return $organization->fresh();
    }

    public function activate(Organization $organization, PlatformUser $actor): Organization
    {
        $organization->update([
            'status' => OrganizationStatus::Active,
            'is_active' => true,
            'archived_at' => null,
        ]);

        $this->audit->log('organization.activated', $actor, $organization, [
            'organization_name' => $organization->name,
        ]);

        $this->dashboard->clearCache();

        return $organization->fresh();
    }

    public function archive(Organization $organization, PlatformUser $actor): Organization
    {
        $organization->update([
            'status' => OrganizationStatus::Archived,
            'is_active' => false,
            'archived_at' => now(),
        ]);

        $this->audit->log('organization.archived', $actor, $organization, [
            'organization_name' => $organization->name,
        ]);

        $this->dashboard->clearCache();

        return $organization->fresh();
    }

    public function updatePlan(Organization $organization, string $plan, PlatformUser $actor): Organization
    {
        $previous = $organization->plan;

        $organization->update(['plan' => $plan]);

        $this->audit->log('organization.plan_changed', $actor, $organization, [
            'previous_plan' => $previous,
            'new_plan' => $plan,
        ]);

        return $organization->fresh();
    }
}
