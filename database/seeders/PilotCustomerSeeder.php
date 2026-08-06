<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\MarketingCampaign;
use App\Models\MarketingProvider;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Platform\OrganizationUpgradeService;
use App\Services\ProjectDefaultsService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Program 15.8 — Five representative pilot organizations with distinct module mixes.
 *
 * Run: php artisan db:seed --class=PilotCustomerSeeder
 * Or:  php artisan pilot:seed
 */
class PilotCustomerSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * @var list<array<string, mixed>>
     */
    private const PILOTS = [
        [
            'code' => 'A',
            'name' => 'Apex Sales Partners',
            'slug' => 'apex-sales-partners',
            'email' => 'ops@apexsales.test',
            'phone' => '+91 22 4100 1001',
            'website' => 'https://apexsales.test',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'postal_code' => '400001',
            'plan' => 'starter',
            'modules' => ['crm', 'tasks'],
            'industry' => 'B2B Sales Agency',
            'owner' => ['name' => 'Ananya Rao', 'email' => 'owner@apexsales.test'],
            'prefix' => 'ASP-',
        ],
        [
            'code' => 'B',
            'name' => 'Meridian People Works',
            'slug' => 'meridian-people-works',
            'email' => 'hr@meridianpeople.test',
            'phone' => '+91 80 4200 2002',
            'website' => 'https://meridianpeople.test',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'country' => 'India',
            'postal_code' => '560001',
            'plan' => 'professional',
            'modules' => ['hrms', 'recruitment', 'projects', 'tasks'],
            'industry' => 'Professional Services HR',
            'owner' => ['name' => 'Meera Krishnan', 'email' => 'owner@meridianpeople.test'],
            'prefix' => 'MPW-',
        ],
        [
            'code' => 'C',
            'name' => 'Cascade Growth Labs',
            'slug' => 'cascade-growth-labs',
            'email' => 'hello@cascadegrowth.test',
            'website' => 'https://cascadegrowth.test',
            'phone' => '+91 44 4300 3003',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'country' => 'India',
            'postal_code' => '600001',
            'plan' => 'professional',
            'modules' => ['crm', 'marketing', 'analytics', 'tasks'],
            'industry' => 'Growth Marketing',
            'owner' => ['name' => 'Karthik Iyer', 'email' => 'owner@cascadegrowth.test'],
            'prefix' => 'CGL-',
        ],
        [
            'code' => 'D',
            'name' => 'Harbor Delivery Collective',
            'slug' => 'harbor-delivery-collective',
            'email' => 'ops@harbordelivery.test',
            'phone' => '+91 20 4400 4004',
            'website' => 'https://harbordelivery.test',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'postal_code' => '411001',
            'plan' => 'professional',
            'modules' => ['projects', 'hrms', 'recruitment', 'marketing', 'tasks'],
            'industry' => 'Delivery & Implementation',
            'owner' => ['name' => 'Priya Deshmukh', 'email' => 'owner@harbordelivery.test'],
            'prefix' => 'HDC-',
        ],
        [
            'code' => 'E',
            'name' => 'Summit Enterprise Group',
            'slug' => 'summit-enterprise-group',
            'email' => 'admin@summitenterprise.test',
            'phone' => '+91 11 4500 5005',
            'website' => 'https://summitenterprise.test',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'country' => 'India',
            'postal_code' => '110001',
            'plan' => 'enterprise',
            'modules' => [
                'crm', 'projects', 'hrms', 'recruitment', 'marketing', 'analytics',
                'finance', 'support', 'workflow', 'tasks', 'customer_portal', 'inventory', 'assets',
            ],
            'industry' => 'Diversified Enterprise',
            'owner' => ['name' => 'Rohan Malhotra', 'email' => 'owner@summitenterprise.test'],
            'prefix' => 'SEG-',
        ],
    ];

    /** @var array<string, mixed> */
    private array $context = [];

    public function run(): void
    {
        $this->ensureFoundationSeeders();

        foreach (self::PILOTS as $pilot) {
            DB::transaction(function () use ($pilot) {
                $this->seedPilot($pilot);
            });
        }

        $this->command?->info('Pilot customers A–E seeded successfully.');
        $this->command?->line('All pilot owner passwords: '.self::PASSWORD);
        foreach (self::PILOTS as $pilot) {
            $this->command?->line(sprintf(
                '  [%s] %s — %s / %s — modules: %s',
                $pilot['code'],
                $pilot['name'],
                $pilot['owner']['email'],
                $pilot['plan'],
                implode(', ', $pilot['modules']),
            ));
        }
    }

    private function ensureFoundationSeeders(): void
    {
        $needsRbac = User::query()->count() === 0
            || ! Schema::hasTable('permissions')
            || Permission::query()->whereNull('organization_id')->count() === 0;

        if ($needsRbac) {
            $this->call(DynamicRbacSeeder::class);
        }

        if ($needsRbac || ! Permission::query()->where('slug', 'projects.view')->exists()) {
            $this->call(ProjectFoundationSeeder::class);
        }

        if ($needsRbac || ! Permission::query()->where('slug', 'tasks.view')->exists()) {
            $this->call(TaskFoundationSeeder::class);
        }

        if ($needsRbac || ! Permission::query()->where('slug', 'resources.view')->exists()) {
            $this->call(ResourcePlanningSeeder::class);
        }

        if (Schema::hasTable('dashboard_widgets')) {
            $this->call(DashboardPlatformSeeder::class);
        }
    }

    /**
     * @param  array<string, mixed>  $pilot
     */
    private function seedPilot(array $pilot): void
    {
        $existing = Organization::query()->where('slug', $pilot['slug'])->first();

        if ($existing && $this->isFullySeeded($existing, $pilot['prefix'])) {
            $this->command?->line("Pilot {$pilot['code']} already seeded — skipping ({$pilot['name']}).");

            return;
        }

        [$organization, $owner] = $this->resolveOrganizationAndOwner($pilot, $existing);

        app(TenantContext::class)->set($organization);

        $this->context = [
            'pilot' => $pilot,
            'organization' => $organization,
            'owner' => $owner,
            'orgId' => $organization->id,
        ];

        app(OrganizationUpgradeService::class)->syncModuleAssignments(
            $organization,
            $pilot['modules'],
            null,
            'manual',
        );

        app(OrganizationUpgradeService::class)->upgrade($organization);

        $this->seedOrgDefaults($organization);
        $users = $this->seedRoleUsers($organization, $pilot);
        $this->seedOrgStructure($pilot);
        $employees = $this->seedEmployees($pilot, $users);

        $modules = $pilot['modules'];

        if (in_array('hrms', $modules, true) || in_array('recruitment', $modules, true)) {
            $this->seedLeaveBalances($employees);
        }

        if (in_array('crm', $modules, true)) {
            $this->seedCrmData($pilot);
        }

        if (in_array('projects', $modules, true)) {
            $this->seedProjectsAndTasks($pilot, $employees, $users);
        }

        if (in_array('marketing', $modules, true)) {
            $this->seedMarketingData($pilot);
        }

        if (Schema::hasTable('dashboard_widgets')) {
            app(DashboardProvisioningService::class)->provision($organization);
        }

        $this->command?->info("Pilot {$pilot['code']}: {$pilot['name']} provisioned.");
    }

    private function isFullySeeded(Organization $organization, string $prefix): bool
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employee_code', 'like', $prefix.'%')
            ->count() >= 5;
    }

    /**
     * @param  array<string, mixed>  $pilot
     * @return array{0: Organization, 1: User}
     */
    private function resolveOrganizationAndOwner(array $pilot, ?Organization $organization): array
    {
        $owner = User::query()->updateOrCreate(
            ['email' => $pilot['owner']['email']],
            [
                'name' => $pilot['owner']['name'],
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );

        if ($organization === null) {
            $organization = Organization::query()->create([
                'name' => $pilot['name'],
                'slug' => $pilot['slug'],
                'email' => $pilot['email'],
                'phone' => $pilot['phone'],
                'website' => $pilot['website'],
                'city' => $pilot['city'],
                'state' => $pilot['state'],
                'country' => $pilot['country'],
                'postal_code' => $pilot['postal_code'],
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
                'is_active' => true,
                'status' => 'active',
                'plan' => $pilot['plan'],
                'description' => 'Pilot '.$pilot['code'].' — '.$pilot['industry'],
                'settings' => [
                    'pilot_code' => $pilot['code'],
                    'industry' => $pilot['industry'],
                    'branding' => [
                        'display_name' => $pilot['name'],
                        'primary_color' => '#0F766E',
                    ],
                ],
            ]);
        } else {
            $organization->update([
                'plan' => $pilot['plan'],
                'is_active' => true,
                'status' => 'active',
            ]);
        }

        if (! $organization->users()->whereKey($owner->id)->exists()) {
            $organization->addMember($owner, 'organization-owner');
        }

        return [$organization->fresh(), $owner];
    }

    private function seedOrgDefaults(Organization $organization): void
    {
        app(ProjectDefaultsService::class)->seedAll($organization);
        app(TaskDefaultsService::class)->seedAll($organization);
    }

    /**
     * @param  array<string, mixed>  $pilot
     * @return array<string, User>
     */
    private function seedRoleUsers(Organization $organization, array $pilot): array
    {
        $slug = $pilot['slug'];
        $definitions = [
            'manager' => ['name' => 'Pilot Manager '.$pilot['code'], 'email' => "manager@{$slug}.test", 'role' => 'manager'],
            'hr' => ['name' => 'HR Lead '.$pilot['code'], 'email' => "hr@{$slug}.test", 'role' => 'hr'],
            'sales' => ['name' => 'Sales Lead '.$pilot['code'], 'email' => "sales@{$slug}.test", 'role' => 'sales-executive'],
            'employee' => ['name' => 'Employee '.$pilot['code'], 'email' => "employee@{$slug}.test", 'role' => 'employee'],
        ];

        $users = ['owner' => $this->context['owner']];

        foreach ($definitions as $key => $def) {
            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ],
            );

            if (! $organization->users()->whereKey($user->id)->exists()) {
                $organization->addMember($user, $def['role']);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, mixed>  $pilot
     */
    private function seedOrgStructure(array $pilot): void
    {
        $orgId = $this->context['orgId'];
        $code = $pilot['code'];

        $branch = Branch::query()->updateOrCreate(
            ['organization_id' => $orgId, 'code' => "HQ-{$code}"],
            [
                'name' => 'HQ — '.$pilot['city'],
                'city' => $pilot['city'],
                'state' => $pilot['state'],
                'country' => $pilot['country'],
                'is_active' => true,
            ],
        );

        $departments = [];
        foreach ([
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Sales', 'code' => 'SAL'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Delivery', 'code' => 'DEL'],
        ] as $dept) {
            $departments[$dept['code']] = Department::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true],
            );
        }

        $designations = [];
        foreach ([
            'OPS' => 'Operations Manager',
            'SAL' => 'Sales Manager',
            'HR' => 'HR Manager',
            'DEL' => 'Project Manager',
        ] as $deptCode => $title) {
            $designations[$deptCode] = Designation::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $deptCode.'-01'],
                ['name' => $title, 'level' => 2, 'is_active' => true],
            );
        }

        $this->context['branch'] = $branch;
        $this->context['departments'] = $departments;
        $this->context['designations'] = $designations;
    }

    /**
     * @param  array<string, mixed>  $pilot
     * @param  array<string, User>  $users
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    private function seedEmployees(array $pilot, array $users)
    {
        $orgId = $this->context['orgId'];
        $branch = $this->context['branch'];
        $departments = $this->context['departments'];
        $designations = $this->context['designations'];
        $prefix = $pilot['prefix'];

        $roster = [
            ['code' => $prefix.'001', 'first' => 'Asha', 'last' => 'Nair', 'dept' => 'OPS', 'user' => $users['manager'], 'email' => $users['manager']->email],
            ['code' => $prefix.'002', 'first' => 'Vikram', 'last' => 'Shah', 'dept' => 'HR', 'user' => $users['hr'], 'email' => $users['hr']->email],
            ['code' => $prefix.'003', 'first' => 'Neha', 'last' => 'Patel', 'dept' => 'SAL', 'user' => $users['sales'], 'email' => $users['sales']->email],
            ['code' => $prefix.'004', 'first' => 'Arjun', 'last' => 'Mehta', 'dept' => 'DEL', 'user' => $users['employee'], 'email' => $users['employee']->email],
            ['code' => $prefix.'005', 'first' => 'Sana', 'last' => 'Qureshi', 'dept' => 'OPS', 'user' => null, 'email' => "sana.q@{$pilot['slug']}.test"],
            ['code' => $prefix.'006', 'first' => 'Dev', 'last' => 'Banerjee', 'dept' => 'DEL', 'user' => null, 'email' => "dev.b@{$pilot['slug']}.test"],
            ['code' => $prefix.'007', 'first' => 'Isha', 'last' => 'Kapoor', 'dept' => 'HR', 'user' => null, 'email' => "isha.k@{$pilot['slug']}.test"],
            ['code' => $prefix.'008', 'first' => 'Rahul', 'last' => 'Joshi', 'dept' => 'SAL', 'user' => null, 'email' => "rahul.j@{$pilot['slug']}.test"],
        ];

        $employees = collect();

        foreach ($roster as $index => $row) {
            $employee = Employee::query()->updateOrCreate(
                ['organization_id' => $orgId, 'employee_code' => $row['code']],
                [
                    'user_id' => $row['user']?->id,
                    'branch_id' => $branch->id,
                    'department_id' => $departments[$row['dept']]->id,
                    'designation_id' => $designations[$row['dept']]->id,
                    'first_name' => $row['first'],
                    'last_name' => $row['last'],
                    'email' => $row['email'],
                    'mobile' => '+91 98'.str_pad((string) (20000000 + ord($pilot['code']) * 100 + $index), 8, '0', STR_PAD_LEFT),
                    'city' => $pilot['city'],
                    'state' => $pilot['state'],
                    'country' => $pilot['country'],
                    'employment_type' => 'full_time',
                    'status' => 'active',
                    'joining_date' => now()->subMonths(6 + $index)->toDateString(),
                ],
            );

            $employees->push($employee);
        }

        $this->context['employees'] = $employees;

        return $employees;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     */
    private function seedLeaveBalances($employees): void
    {
        $orgId = $this->context['orgId'];
        $leaveTypes = [];

        foreach (config('hrms.default_leave_types', []) as $key => $defaults) {
            $leaveTypes[$key] = LeaveType::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $defaults['code']],
                [
                    'name' => $defaults['name'],
                    'is_paid' => $defaults['is_paid'],
                    'requires_approval' => $defaults['requires_approval'],
                    'requires_hr_approval' => $defaults['requires_hr_approval'] ?? false,
                    'allow_half_day' => $defaults['allow_half_day'] ?? true,
                    'max_days_per_year' => $defaults['max_days_per_year'],
                    'allocation_days' => $defaults['max_days_per_year'],
                    'is_active' => true,
                ],
            );
        }

        if ($leaveTypes === []) {
            return;
        }

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                $entitled = (float) ($leaveType->allocation_days ?? 12);
                LeaveBalance::query()->updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => (int) now()->year,
                    ],
                    [
                        'entitled' => $entitled,
                        'used' => 2,
                        'pending' => 0,
                        'balance' => $entitled - 2,
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pilot
     */
    private function seedCrmData(array $pilot): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $statuses = array_keys(config('leads.statuses', ['new' => 'New']));

        $companies = [
            'Northwind Traders', 'Contoso Retail', 'Fabrikam Logistics',
            'Adventure Works', 'Litware Media', 'Tailspin Toys',
        ];

        foreach ($companies as $index => $company) {
            Lead::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'company' => $company,
                ],
                [
                    'name' => fake()->name(),
                    'email' => Str::slug($company).'@prospect.'.$pilot['slug'].'.test',
                    'phone' => '+91 98'.str_pad((string) (30000000 + $index), 8, '0', STR_PAD_LEFT),
                    'source' => ['website', 'referral', 'linkedin', 'google_ads'][$index % 4],
                    'industry' => $pilot['industry'],
                    'priority' => ['low', 'medium', 'high'][$index % 3],
                    'status' => $statuses[$index % count($statuses)],
                    'tags' => ['pilot', 'customer-'.$pilot['code']],
                    'created_by' => $owner->id,
                ],
            );
        }

        foreach (array_slice($companies, 0, 4) as $index => $company) {
            Customer::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'company' => $company.' Accounts',
                ],
                [
                    'name' => fake()->name(),
                    'email' => 'accounts@'.Str::slug($company).'.test',
                    'phone' => '+91 22 5'.str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                    'industry' => $pilot['industry'],
                    'status' => 'active',
                    'city' => $pilot['city'],
                    'country' => $pilot['country'],
                    'tags' => ['pilot'],
                    'created_by' => $owner->id,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $pilot
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @param  array<string, User>  $users
     */
    private function seedProjectsAndTasks(array $pilot, $employees, array $users): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $projectService = app(ProjectService::class);
        $taskService = app(TaskService::class);

        $statuses = TaskStatus::query()->where('organization_id', $organization->id)->get()->keyBy('slug');
        $priorities = TaskPriority::query()->where('organization_id', $organization->id)->get()->keyBy('slug');

        $definitions = [
            ['name' => $pilot['name'].' — Onboarding Rollout', 'priority' => 'high', 'completion' => 45],
            ['name' => $pilot['name'].' — Process Standardization', 'priority' => 'medium', 'completion' => 20],
        ];

        foreach ($definitions as $definition) {
            $project = Project::query()
                ->where('organization_id', $organization->id)
                ->where('name', $definition['name'])
                ->first();

            if ($project === null) {
                $project = $projectService->create([
                    'organization_id' => $organization->id,
                    'name' => $definition['name'],
                    'description' => 'Pilot initiative for '.$pilot['industry'],
                    'owner_id' => $owner->id,
                    'manager_id' => ($users['manager'] ?? $owner)->id,
                    'priority' => $definition['priority'],
                    'start_date' => now()->subMonth()->toDateString(),
                    'planned_end_date' => now()->addMonths(3)->toDateString(),
                    'estimated_budget' => 500000,
                    'completion_percentage' => $definition['completion'],
                ], $owner);
            }

            foreach ([
                ['name' => 'Discovery', 'status' => 'completed', 'sequence' => 1],
                ['name' => 'Build', 'status' => 'in_progress', 'sequence' => 2],
                ['name' => 'Handover', 'status' => 'pending', 'sequence' => 3],
            ] as $milestone) {
                ProjectMilestone::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'project_id' => $project->id,
                        'name' => $milestone['name'],
                    ],
                    [
                        'sequence' => $milestone['sequence'],
                        'due_date' => now()->addMonths($milestone['sequence'])->toDateString(),
                        'status' => $milestone['status'],
                        'completed_at' => $milestone['status'] === 'completed' ? now()->subWeek() : null,
                    ],
                );
            }

            foreach ($employees->filter(fn (Employee $e) => $e->user_id !== null)->take(3) as $member) {
                ProjectMember::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'project_id' => $project->id,
                        'user_id' => $member->user_id,
                    ],
                    [
                        'project_role' => 'contributor',
                        'joined_at' => now()->subWeeks(2),
                        'is_active' => true,
                    ],
                );
            }

            if ($statuses->isNotEmpty() && $priorities->isNotEmpty()) {
                $todo = $statuses->get('to-do') ?? $statuses->first();
                $priority = $priorities->get('medium') ?? $priorities->first();

                for ($i = 1; $i <= 3; $i++) {
                    $title = "Pilot task {$i} — {$project->name}";
                    $exists = Task::query()
                        ->where('organization_id', $organization->id)
                        ->where('project_id', $project->id)
                        ->where('title', $title)
                        ->exists();

                    if (! $exists) {
                        $taskService->createWorkManagement([
                            'organization_id' => $organization->id,
                            'project_id' => $project->id,
                            'title' => $title,
                            'description' => 'Operational validation task for Program 15.8.',
                            'status_id' => $todo->id,
                            'priority_id' => $priority->id,
                            'assigned_to' => ($users['employee'] ?? $owner)->id,
                            'due_date' => now()->addWeeks($i)->toDateString(),
                        ], $owner);
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pilot
     */
    private function seedMarketingData(array $pilot): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $orgId = $organization->id;

        if (Schema::hasTable('marketing_providers')) {
            MarketingProvider::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'slug' => 'pilot-'.$pilot['code'].'-meta',
                ],
                [
                    'display_name' => 'Pilot Meta Ads',
                    'status' => MarketingProvider::STATUS_CONNECTED,
                    'connected_at' => now()->subWeeks(2),
                    'capabilities' => ['leads', 'campaigns'],
                    'metadata' => ['pilot' => true, 'customer' => $pilot['code']],
                ],
            );
        }

        if (! Schema::hasTable('marketing_campaigns')) {
            return;
        }

        $slug = 'pilot-'.$pilot['code'].'-awareness';

        MarketingCampaign::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'slug' => $slug,
            ],
            [
                'created_by' => $owner->id,
                'name' => $pilot['name'].' — Awareness Campaign',
                'status' => MarketingCampaign::STATUS_ACTIVE,
                'description' => 'Pilot demand-gen campaign for '.$pilot['industry'],
                'budget_amount' => 150000,
                'budget_currency' => 'INR',
                'channels' => ['linkedin', 'google_ads'],
                'audience' => ['persona' => 'Buyer', 'size' => '50-500'],
                'utm_campaign' => $slug,
                'starts_at' => now()->subWeeks(2),
                'ends_at' => now()->addMonths(1),
            ],
        );
    }
}
