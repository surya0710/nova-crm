<?php

namespace App\Services\Platform;

use App\Enums\OrganizationStatus;
use App\Enums\UserAccountStatus;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\HrmsShift;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\OrganizationOnboarding;
use App\Models\PlatformUser;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Administration\OrganizationBrandingService;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Identity\UserInvitationService;
use App\Services\OrganizationMailConfig;
use App\Services\OrganizationMailer;
use App\Services\OrganizationMemberService;
use App\Services\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OnboardingWizardService
{
    public function __construct(
        protected OrganizationManagementService $organizations,
        protected PlatformLicensingService $licensing,
        protected PlatformSubscriptionService $subscriptions,
        protected PlatformProviderService $providers,
        protected PlatformAuditService $audit,
        protected OrganizationBrandingService $branding,
        protected OrganizationMemberService $members,
        protected UserInvitationService $invitations,
        protected OrganizationMailer $mailer,
        protected ModuleSubscriptionService $modules,
        protected TenantContext $tenant,
        protected PlatformDashboardService $dashboard,
    ) {}

    public function start(PlatformUser $actor, array $prefill = []): OrganizationOnboarding
    {
        $onboarding = OrganizationOnboarding::query()->create([
            'initiated_by_platform_user_id' => $actor->id,
            'status' => OrganizationOnboarding::STATUS_DRAFT,
            'current_step' => 'organization',
            'progress_percent' => 0,
            'completed_steps' => [],
            'skipped_steps' => [],
            'step_data' => ['organization' => $prefill],
            'checklist' => [],
            'metadata' => [],
            'started_at' => now(),
        ]);

        $this->audit->log('onboarding.started', $actor, null, [
            'onboarding_id' => $onboarding->id,
        ]);

        $this->dashboard->clearCache();

        return $onboarding;
    }

    public function resume(OrganizationOnboarding $onboarding): OrganizationOnboarding
    {
        if ($onboarding->isTerminal()) {
            throw ValidationException::withMessages([
                'onboarding' => __('This onboarding session is already finished.'),
            ]);
        }

        if ($onboarding->status === OrganizationOnboarding::STATUS_DRAFT) {
            $onboarding->forceFill(['status' => OrganizationOnboarding::STATUS_IN_PROGRESS])->save();
        }

        return $onboarding->fresh(['organization', 'initiator']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveDraft(OrganizationOnboarding $onboarding, string $step, array $payload): OrganizationOnboarding
    {
        $this->assertActive($onboarding);
        $this->assertKnownStep($step);

        $data = $onboarding->step_data ?? [];
        $data[$step] = array_replace_recursive($data[$step] ?? [], $payload);

        $onboarding->forceFill([
            'step_data' => $data,
            'current_step' => $step,
            'status' => $onboarding->status === OrganizationOnboarding::STATUS_DRAFT
                ? OrganizationOnboarding::STATUS_DRAFT
                : OrganizationOnboarding::STATUS_IN_PROGRESS,
        ])->save();

        return $onboarding->fresh(['organization']);
    }

    /**
     * Complete (or skip) the current step and advance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function completeStep(
        OrganizationOnboarding $onboarding,
        string $step,
        array $payload = [],
        bool $skip = false,
        ?PlatformUser $actor = null,
    ): OrganizationOnboarding {
        $this->assertActive($onboarding);
        $this->assertKnownStep($step);

        if ($onboarding->current_step !== $step && ! $onboarding->isStepCompleted($step)) {
            // Allow completing the current step only, unless re-saving completed.
            if ($step !== $onboarding->current_step) {
                throw ValidationException::withMessages([
                    'step' => __('Complete steps in order. Current step is :step.', [
                        'step' => $onboarding->current_step,
                    ]),
                ]);
            }
        }

        $actor ??= $onboarding->initiator;

        try {
            if ($skip) {
                $this->markSkipped($onboarding, $step);
            } else {
                $this->saveDraft($onboarding, $step, $payload);
                $onboarding = $onboarding->fresh();
                $this->executeStep($onboarding, $step, $payload, $actor);
                $this->markCompleted($onboarding, $step);
            }

            $onboarding = $onboarding->fresh();
            $next = $this->nextStep($step);

            $onboarding->forceFill([
                'status' => OrganizationOnboarding::STATUS_IN_PROGRESS,
                'current_step' => $next ?? 'go_live',
                'progress_percent' => $this->calculateProgress($onboarding),
                'last_error' => null,
            ])->save();

            $this->audit->log($skip ? 'onboarding.step_skipped' : 'onboarding.step_completed', $actor, $onboarding->organization, [
                'onboarding_id' => $onboarding->id,
                'step' => $step,
            ]);

            return $onboarding->fresh(['organization']);
        } catch (\Throwable $e) {
            $onboarding->forceFill([
                'status' => OrganizationOnboarding::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ])->save();

            $this->audit->log('onboarding.validation_failed', $actor, $onboarding->organization, [
                'onboarding_id' => $onboarding->id,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function goBack(OrganizationOnboarding $onboarding, string $toStep): OrganizationOnboarding
    {
        $this->assertActive($onboarding);
        $this->assertKnownStep($toStep);

        $keys = OrganizationOnboarding::stepKeys();
        $targetIndex = array_search($toStep, $keys, true);
        $currentIndex = array_search($onboarding->current_step, $keys, true);

        if ($targetIndex === false || ($currentIndex !== false && $targetIndex > $currentIndex)) {
            throw ValidationException::withMessages([
                'step' => __('Cannot navigate forward with Previous.'),
            ]);
        }

        $onboarding->forceFill([
            'current_step' => $toStep,
            'status' => OrganizationOnboarding::STATUS_IN_PROGRESS,
        ])->save();

        return $onboarding->fresh(['organization']);
    }

    public function finish(OrganizationOnboarding $onboarding, PlatformUser $actor): OrganizationOnboarding
    {
        $this->assertActive($onboarding);

        if ($onboarding->current_step !== 'go_live' && ! $onboarding->isStepCompleted('go_live')) {
            throw ValidationException::withMessages([
                'step' => __('Complete the go-live checklist before finishing.'),
            ]);
        }

        $checklist = $this->buildChecklist($onboarding);
        $blocking = collect($checklist)->where('required', true)->where('passed', false)->values();

        if ($blocking->isNotEmpty()) {
            throw ValidationException::withMessages([
                'checklist' => __('Required go-live checks failed: :items', [
                    'items' => $blocking->pluck('label')->implode(', '),
                ]),
            ]);
        }

        if (! $onboarding->isStepCompleted('go_live')) {
            $this->markCompleted($onboarding, 'go_live');
        }

        $onboarding->forceFill([
            'status' => OrganizationOnboarding::STATUS_COMPLETED,
            'checklist' => $checklist,
            'progress_percent' => 100,
            'completed_at' => now(),
            'current_step' => 'go_live',
        ])->save();

        $this->audit->log('onboarding.completed', $actor, $onboarding->organization, [
            'onboarding_id' => $onboarding->id,
            'checklist' => $checklist,
        ]);

        $this->dashboard->clearCache();

        return $onboarding->fresh(['organization']);
    }

    /**
     * @return array{steps: list<array<string, mixed>>, progress_percent: int, checklist: list<array<string, mixed>>}
     */
    public function progress(OrganizationOnboarding $onboarding): array
    {
        $keys = OrganizationOnboarding::stepKeys();
        $steps = [];

        foreach ($keys as $key) {
            $meta = config('onboarding.steps.'.$key, []);
            $steps[] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'description' => $meta['description'] ?? '',
                'completed' => $onboarding->isStepCompleted($key),
                'skipped' => $onboarding->isStepSkipped($key),
                'current' => $onboarding->current_step === $key,
            ];
        }

        return [
            'status' => $onboarding->status,
            'current_step' => $onboarding->current_step,
            'progress_percent' => $this->calculateProgress($onboarding),
            'steps' => $steps,
            'checklist' => $this->buildChecklist($onboarding),
            'organization_id' => $onboarding->organization_id,
        ];
    }

    /**
     * Dashboard summary for platform home.
     *
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array
    {
        $base = OrganizationOnboarding::query();

        return [
            'pending_setup' => (clone $base)->whereIn('status', [
                OrganizationOnboarding::STATUS_DRAFT,
                OrganizationOnboarding::STATUS_IN_PROGRESS,
                OrganizationOnboarding::STATUS_FAILED,
            ])->count(),
            'in_progress' => (clone $base)->where('status', OrganizationOnboarding::STATUS_IN_PROGRESS)->count(),
            'ready' => (clone $base)->where('status', OrganizationOnboarding::STATUS_COMPLETED)->count(),
            'failed' => (clone $base)->where('status', OrganizationOnboarding::STATUS_FAILED)->count(),
            'recently_completed' => OrganizationOnboarding::query()
                ->with('organization:id,name')
                ->where('status', OrganizationOnboarding::STATUS_COMPLETED)
                ->latest('completed_at')
                ->limit(5)
                ->get(),
            'active_sessions' => OrganizationOnboarding::query()
                ->with('organization:id,name')
                ->whereIn('status', [
                    OrganizationOnboarding::STATUS_DRAFT,
                    OrganizationOnboarding::STATUS_IN_PROGRESS,
                    OrganizationOnboarding::STATUS_FAILED,
                ])
                ->latest('updated_at')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeStep(
        OrganizationOnboarding $onboarding,
        string $step,
        array $payload,
        PlatformUser $actor,
    ): void {
        match ($step) {
            'organization' => $this->executeOrganization($onboarding, $payload, $actor),
            'modules' => $this->executeModules($onboarding, $payload, $actor),
            'structure' => $this->executeStructure($onboarding, $payload),
            'users' => $this->executeUsers($onboarding, $payload),
            'imports' => $this->executeImports($onboarding, $payload),
            'branding' => $this->executeBranding($onboarding, $payload),
            'communication' => $this->executeCommunication($onboarding, $payload),
            'providers' => $this->executeProviders($onboarding, $payload),
            'go_live' => null,
            default => throw new InvalidArgumentException("Unknown onboarding step [{$step}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeOrganization(OrganizationOnboarding $onboarding, array $payload, PlatformUser $actor): void
    {
        if ($onboarding->organization_id) {
            $organization = Organization::query()->findOrFail($onboarding->organization_id);
            $settings = $organization->settings ?? [];
            $settings['onboarding'] = array_merge($settings['onboarding'] ?? [], [
                'industry' => $payload['industry'] ?? null,
                'date_format' => $payload['date_format'] ?? null,
                'fiscal_year_start' => $payload['fiscal_year_start'] ?? null,
            ]);

            $organization->update([
                'name' => $payload['name'] ?? $organization->name,
                'email' => $payload['email'] ?? $organization->email,
                'phone' => $payload['phone'] ?? $organization->phone,
                'website' => $payload['website'] ?? $organization->website,
                'timezone' => $payload['timezone'] ?? $organization->timezone,
                'currency' => strtoupper($payload['currency'] ?? $organization->currency ?? 'USD'),
                'address_line_1' => $payload['address_line_1'] ?? $organization->address_line_1,
                'address_line_2' => $payload['address_line_2'] ?? $organization->address_line_2,
                'city' => $payload['city'] ?? $organization->city,
                'state' => $payload['state'] ?? $organization->state,
                'postal_code' => $payload['postal_code'] ?? $organization->postal_code,
                'country' => $payload['country'] ?? $organization->country,
                'settings' => $settings,
            ]);

            return;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => __('Organization name is required.')]);
        }

        $organization = $this->organizations->create([
            'name' => $name,
            'slug' => $payload['slug'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'website' => $payload['website'] ?? null,
            'plan' => $payload['plan'] ?? 'starter',
            'status' => OrganizationStatus::Active->value,
            'timezone' => $payload['timezone'] ?? 'UTC',
            'currency' => $payload['currency'] ?? 'USD',
            'template_version_id' => $payload['template_version_id'] ?? null,
        ], $actor);

        $settings = $organization->settings ?? [];
        $settings['onboarding'] = [
            'industry' => $payload['industry'] ?? null,
            'date_format' => $payload['date_format'] ?? 'Y-m-d',
            'fiscal_year_start' => $payload['fiscal_year_start'] ?? '01-01',
            'wizard_id' => $onboarding->id,
        ];
        $organization->update([
            'address_line_1' => $payload['address_line_1'] ?? null,
            'address_line_2' => $payload['address_line_2'] ?? null,
            'city' => $payload['city'] ?? null,
            'state' => $payload['state'] ?? null,
            'postal_code' => $payload['postal_code'] ?? null,
            'country' => $payload['country'] ?? null,
            'settings' => $settings,
        ]);

        $onboarding->forceFill(['organization_id' => $organization->id])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeModules(OrganizationOnboarding $onboarding, array $payload, PlatformUser $actor): void
    {
        $organization = $this->requireOrganization($onboarding);

        if (! empty($payload['plan'])) {
            $this->subscriptions->assignPlan($organization, (string) $payload['plan'], $actor);
            $organization = $organization->fresh();
        }

        $selected = array_values(array_intersect(
            Arr::wrap($payload['modules'] ?? []),
            config('onboarding.selectable_modules', [])
        ));

        if ($selected === []) {
            $selected = $this->modules->availableModules($organization);
        }

        $this->licensing->assignModules($organization, $selected, $actor);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeStructure(OrganizationOnboarding $onboarding, array $payload): void
    {
        $organization = $this->requireOrganization($onboarding);
        $this->tenant->set($organization);

        if (! empty($payload['branch']['name'])) {
            Branch::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $payload['branch']['code'] ?? Str::upper(Str::slug($payload['branch']['name'], '_')),
                ],
                [
                    'name' => $payload['branch']['name'],
                    'city' => $payload['branch']['city'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        if (! empty($payload['department']['name'])) {
            Department::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $payload['department']['code'] ?? Str::upper(Str::slug($payload['department']['name'], '_')),
                ],
                [
                    'name' => $payload['department']['name'],
                    'is_active' => true,
                ]
            );
        }

        if (! empty($payload['designation']['name'])) {
            Designation::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $payload['designation']['code'] ?? Str::upper(Str::slug($payload['designation']['name'], '_')),
                ],
                [
                    'name' => $payload['designation']['name'],
                    'is_active' => true,
                ]
            );
        }

        if (! empty($payload['shift']['name'])) {
            HrmsShift::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'code' => $payload['shift']['code'] ?? Str::upper(Str::slug($payload['shift']['name'], '_')),
                ],
                [
                    'name' => $payload['shift']['name'],
                    'start_time' => $payload['shift']['start_time'] ?? '09:00:00',
                    'end_time' => $payload['shift']['end_time'] ?? '18:00:00',
                    'is_active' => true,
                    'is_default' => true,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeUsers(OrganizationOnboarding $onboarding, array $payload): void
    {
        $organization = $this->requireOrganization($onboarding);
        $this->tenant->set($organization);

        $admin = $payload['administrator'] ?? null;
        if (! is_array($admin) || empty($admin['email']) || empty($admin['name'])) {
            throw ValidationException::withMessages([
                'administrator.email' => __('Organization administrator name and email are required.'),
            ]);
        }

        $email = strtolower(trim((string) $admin['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $admin['name'],
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'account_status' => UserAccountStatus::PendingInvitation,
                'portal_access_enabled' => true,
            ]);
        }

        if (! $organization->users()->where('users.id', $user->id)->exists()) {
            $organization->addMember($user, $admin['role'] ?? 'organization-owner');
        }

        $invite = (bool) ($admin['send_invitation'] ?? true);
        if ($invite) {
            $this->invitations->invite($user, $organization, $user, [
                'send_email' => ($admin['notify'] ?? true) === true,
            ]);
        }

        foreach (Arr::wrap($payload['additional_members'] ?? []) as $member) {
            if (empty($member['email']) || empty($member['name'])) {
                continue;
            }
            $this->members->addMember($organization, [
                'name' => $member['name'],
                'email' => $member['email'],
                'role' => $member['role'] ?? 'organization-member',
                'send_invitation' => (bool) ($member['send_invitation'] ?? true),
                'notify' => (bool) ($member['notify'] ?? true),
            ], $user);
        }

        $data = $onboarding->step_data ?? [];
        $data['users']['administrator_user_id'] = $user->id;
        $data['users']['import_employees'] = (bool) ($payload['import_employees'] ?? false);
        $onboarding->forceFill(['step_data' => $data])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeImports(OrganizationOnboarding $onboarding, array $payload): void
    {
        $organization = $this->requireOrganization($onboarding);
        $selected = Arr::wrap($payload['entities'] ?? []);

        $data = $onboarding->step_data ?? [];
        $data['imports'] = [
            'entities' => $selected,
            'notes' => $payload['notes'] ?? null,
            'deferred' => (bool) ($payload['deferred'] ?? false),
            'import_links' => collect($selected)->mapWithKeys(fn (string $entity) => [
                $entity => url('/administration/imports/'.$entity.'/create'),
            ])->all(),
        ];
        $onboarding->forceFill(['step_data' => $data])->save();

        // Progress snapshot from existing Import Center sessions for this org.
        $recent = ImportSession::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'entity_type', 'status', 'created_count', 'failed_count', 'created_at']);

        $data = $onboarding->step_data ?? [];
        $data['imports']['recent_sessions'] = $recent->toArray();
        $onboarding->forceFill(['step_data' => $data])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeBranding(OrganizationOnboarding $onboarding, array $payload): void
    {
        $organization = $this->requireOrganization($onboarding);
        $actor = $organization->primaryOwner() ?? $organization->users()->first();

        if (! $actor) {
            // Persist branding without a user actor id.
            $settings = $organization->settings ?? [];
            $settings['branding'] = [
                'primary_color' => $payload['primary_color'] ?? null,
                'accent_color' => $payload['accent_color'] ?? null,
                'email_from_name' => $payload['email_from_name'] ?? $organization->name,
                'email_header_text' => $payload['email_header_text'] ?? $organization->name,
                'login_headline' => $payload['login_headline'] ?? null,
                'login_tagline' => $payload['login_tagline'] ?? null,
                'document_footer' => $payload['document_footer'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ];
            $organization->update(['settings' => $settings]);

            return;
        }

        $this->branding->update($organization, $payload, $actor);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeCommunication(OrganizationOnboarding $onboarding, array $payload): void
    {
        $organization = $this->requireOrganization($onboarding);
        $settings = $organization->settings ?? [];
        $settings['mail'] = OrganizationMailConfig::mergeSettings($settings['mail'] ?? [], $payload);
        $organization->update(['settings' => $settings]);

        $verify = (bool) ($payload['verify'] ?? false);
        if ($verify) {
            if (! $this->mailer->isConfigured($organization->fresh())) {
                throw ValidationException::withMessages([
                    'mail' => __('Email is not fully configured. Enable mail and provide SMTP or log driver settings.'),
                ]);
            }
        }

        $data = $onboarding->step_data ?? [];
        $data['communication']['verified'] = $verify && $this->mailer->isConfigured($organization->fresh());
        $onboarding->forceFill(['step_data' => $data])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function executeProviders(OrganizationOnboarding $onboarding, array $payload): void
    {
        $summary = $this->providers->healthSummary();
        $data = $onboarding->step_data ?? [];
        $data['providers'] = [
            'summary' => $summary,
            'acknowledged' => (bool) ($payload['acknowledged'] ?? false),
            'skipped_providers' => Arr::wrap($payload['skipped_providers'] ?? []),
        ];
        $onboarding->forceFill(['step_data' => $data])->save();
    }

    /**
     * @return list<array{key: string, label: string, passed: bool, required: bool, warning?: string}>
     */
    public function buildChecklist(OrganizationOnboarding $onboarding): array
    {
        $organization = $onboarding->organization;
        $items = [];

        $items[] = [
            'key' => 'organization_created',
            'label' => 'Organization created',
            'passed' => $organization !== null,
            'required' => true,
        ];

        $enabled = $organization ? $this->modules->enabledModules($organization) : [];
        $items[] = [
            'key' => 'modules_licensed',
            'label' => 'Modules licensed',
            'passed' => $organization !== null && count($enabled) > 0,
            'required' => true,
        ];

        $rbacReady = $organization
            ? $organization->roles()->exists()
            : false;
        $items[] = [
            'key' => 'rbac_provisioned',
            'label' => 'RBAC provisioned',
            'passed' => $rbacReady,
            'required' => true,
        ];

        $branchOk = $organization
            ? Branch::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->exists()
            : false;
        $items[] = [
            'key' => 'branch_configured',
            'label' => 'Branch configured',
            'passed' => $branchOk || $onboarding->isStepSkipped('structure'),
            'required' => false,
            'warning' => $branchOk ? null : 'No branch created yet',
        ];

        $deptOk = $organization
            ? Department::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->exists()
            : false;
        $items[] = [
            'key' => 'department_configured',
            'label' => 'Department configured',
            'passed' => $deptOk || $onboarding->isStepSkipped('structure'),
            'required' => false,
            'warning' => $deptOk ? null : 'No department created yet',
        ];

        $employeesImported = $organization
            ? ImportSession::query()->withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('entity_type', 'employee')
                ->where('status', 'completed')
                ->exists()
            : false;
        $items[] = [
            'key' => 'employees_imported',
            'label' => 'Employees imported',
            'passed' => $employeesImported || $onboarding->isStepSkipped('imports'),
            'required' => false,
            'warning' => $employeesImported ? null : 'Employee import not completed',
        ];

        $invitesSent = $organization
            ? UserInvitation::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->exists()
            : false;
        $items[] = [
            'key' => 'invitations_sent',
            'label' => 'Invitations sent',
            'passed' => $invitesSent || ($organization && $organization->users()->exists()),
            'required' => true,
        ];

        $branding = is_array($organization?->settings['branding'] ?? null) ? $organization->settings['branding'] : [];
        $brandingOk = $organization && ($organization->logo || ! empty($branding['primary_color']) || ! empty($branding['email_from_name']));
        $items[] = [
            'key' => 'branding_configured',
            'label' => 'Branding configured',
            'passed' => $brandingOk || $onboarding->isStepSkipped('branding'),
            'required' => false,
            'warning' => $brandingOk ? null : 'Branding not configured',
        ];

        $mailOk = $organization ? $this->mailer->isConfigured($organization) : false;
        $items[] = [
            'key' => 'email_working',
            'label' => 'Email configured',
            'passed' => $mailOk || $onboarding->isStepSkipped('communication'),
            'required' => false,
            'warning' => $mailOk ? null : 'Organization email not configured',
        ];

        $providerSummary = $this->providers->healthSummary();
        $providersOk = ($providerSummary['status'] ?? '') !== 'critical'
            || $onboarding->isStepSkipped('providers')
            || ! empty($onboarding->step_data['providers']['acknowledged']);
        $items[] = [
            'key' => 'providers_connected',
            'label' => 'Providers reviewed',
            'passed' => $providersOk,
            'required' => false,
            'warning' => $providersOk ? null : 'Provider health is critical',
        ];

        return $items;
    }

    protected function markCompleted(OrganizationOnboarding $onboarding, string $step): void
    {
        $completed = array_values(array_unique(array_merge($onboarding->completed_steps ?? [], [$step])));
        $skipped = array_values(array_filter(
            $onboarding->skipped_steps ?? [],
            static fn (string $s) => $s !== $step
        ));

        $onboarding->forceFill([
            'completed_steps' => $completed,
            'skipped_steps' => $skipped,
        ])->save();
    }

    protected function markSkipped(OrganizationOnboarding $onboarding, string $step): void
    {
        if (in_array($step, ['organization', 'modules', 'go_live'], true)) {
            throw ValidationException::withMessages([
                'step' => __('This step cannot be skipped.'),
            ]);
        }

        $skipped = array_values(array_unique(array_merge($onboarding->skipped_steps ?? [], [$step])));
        $onboarding->forceFill(['skipped_steps' => $skipped])->save();
    }

    protected function nextStep(string $step): ?string
    {
        $keys = OrganizationOnboarding::stepKeys();
        $index = array_search($step, $keys, true);
        if ($index === false) {
            return null;
        }

        return $keys[$index + 1] ?? null;
    }

    protected function calculateProgress(OrganizationOnboarding $onboarding): int
    {
        $total = max(1, count(OrganizationOnboarding::stepKeys()));
        $done = count(array_unique(array_merge(
            $onboarding->completed_steps ?? [],
            $onboarding->skipped_steps ?? []
        )));

        return (int) min(100, round(($done / $total) * 100));
    }

    protected function requireOrganization(OrganizationOnboarding $onboarding): Organization
    {
        if (! $onboarding->organization_id) {
            throw ValidationException::withMessages([
                'organization' => __('Complete the organization step first.'),
            ]);
        }

        return Organization::query()->findOrFail($onboarding->organization_id);
    }

    protected function assertActive(OrganizationOnboarding $onboarding): void
    {
        if ($onboarding->isTerminal()) {
            throw ValidationException::withMessages([
                'onboarding' => __('This onboarding session is closed.'),
            ]);
        }
    }

    protected function assertKnownStep(string $step): void
    {
        if (! array_key_exists($step, config('onboarding.steps', []))) {
            throw new InvalidArgumentException("Unknown onboarding step [{$step}].");
        }
    }
}
