<?php

namespace Database\Seeders;

use App\Models\MarketingCampaign;
use App\Models\MarketingProvider;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\EmployeeDocument;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsAnnouncement;
use App\Models\HrmsShift;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Lead;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\WorkloadSnapshot;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\ProjectDefaultsService;
use App\Services\ProjectService;
use App\Services\Recruitment\InterviewStageService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Enterprise demo dataset for Konnect Nex presentation screenshots.
 *
 * Run: php artisan db:seed --class=PresentationDemoSeeder
 * Or:  php artisan demo:seed-presentation
 */
class PresentationDemoSeeder extends Seeder
{
    private const ORG_NAME = 'Nova Enterprises';

    private const OWNER_EMAIL = 'demo@novacrm.test';

    private const EMPLOYEE_CODE_PREFIX = 'NE-';

    private const MIN_EMPLOYEES = 25;

    /** @var array<string, mixed> */
    private array $context = [];

    public function run(): void
    {
        $organization = Organization::query()->where('name', self::ORG_NAME)->first();

        if ($organization && $this->isFullySeeded($organization)) {
            app(TenantContext::class)->set($organization);
            $owner = User::query()->where('email', self::OWNER_EMAIL)->first()
                ?? $organization->users()->first();
            $this->context = [
                'organization' => $organization,
                'owner' => $owner,
                'orgId' => $organization->id,
            ];
            $this->seedMarketingData();
            $this->command?->info('Nova Enterprises demo data already exists — marketing demo refreshed.');
            $this->command?->line('Login: '.self::OWNER_EMAIL.' / password');

            return;
        }

        $this->ensureFoundationSeeders();

        DB::transaction(function () use ($organization) {
            [$organization, $owner] = $this->resolveOrganizationAndOwner($organization);
            $this->context = [
                'organization' => $organization,
                'owner' => $owner,
                'orgId' => $organization->id,
            ];

            app(TenantContext::class)->set($organization);

            $this->seedOrgDefaults($organization);
            $this->seedOrgStructure();
            $this->seedEmployees();
            $this->seedShiftsAndAttendance();
            $this->seedLeaveData();
            $this->seedAnnouncements();
            $this->seedAssetsAndDocuments();
            $this->seedRecruitment();
            $this->seedProjectsAndTasks();
            $this->seedResourcePlanning();
            $this->seedCrmData();
            $this->seedMarketingData();
            $this->ensureDashboardForOrg($organization);
        });

        $this->command?->info('Nova Enterprises presentation demo seeded successfully.');
        $this->command?->line('Login: '.self::OWNER_EMAIL.' / password');
        $this->command?->line('Organization: '.self::ORG_NAME);
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

    private function isFullySeeded(Organization $organization): bool
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('employee_code', 'like', self::EMPLOYEE_CODE_PREFIX.'%')
            ->count() >= self::MIN_EMPLOYEES;
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function resolveOrganizationAndOwner(?Organization $organization): array
    {
        $owner = User::query()->updateOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Demo Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if ($organization === null) {
            $organization = Organization::query()->create([
                'name' => self::ORG_NAME,
                'slug' => Organization::generateUniqueSlug(self::ORG_NAME),
                'email' => 'contact@novaenterprises.test',
                'phone' => '+91 22 4000 1000',
                'website' => 'https://novaenterprises.test',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '400001',
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
                'is_active' => true,
                'status' => 'active',
                'plan' => 'enterprise',
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
        app(InterviewStageService::class)->ensureDefaultStages($organization, $this->context['owner']);
    }

    private function seedOrgStructure(): void
    {
        $orgId = $this->context['orgId'];

        $branch = Branch::query()->updateOrCreate(
            ['organization_id' => $orgId, 'code' => 'HQ-MUM'],
            [
                'name' => 'HQ — Mumbai',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'is_active' => true,
            ],
        );

        $branchBlr = Branch::query()->updateOrCreate(
            ['organization_id' => $orgId, 'code' => 'BLR-01'],
            [
                'name' => 'Bengaluru Delivery Center',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'India',
                'is_active' => true,
            ],
        );

        $departments = [];
        foreach ([
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Sales', 'code' => 'SAL'],
            ['name' => 'HR', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Operations', 'code' => 'OPS'],
        ] as $dept) {
            $departments[$dept['code']] = Department::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true],
            );
        }

        $designations = [];
        $designationMap = [
            'ENG' => ['Chief Technology Officer', 'Engineering Manager', 'Senior Software Engineer', 'Software Engineer', 'QA Engineer', 'DevOps Engineer'],
            'SAL' => ['Sales Director', 'Account Manager', 'Sales Executive', 'Business Development Manager'],
            'HR' => ['HR Manager', 'HR Executive', 'Talent Acquisition Specialist', 'HR Business Partner'],
            'FIN' => ['Finance Manager', 'Financial Analyst', 'Accountant', 'Payroll Specialist'],
            'OPS' => ['Operations Manager', 'Operations Executive', 'Office Administrator', 'Facilities Coordinator'],
        ];

        foreach ($designationMap as $deptCode => $titles) {
            foreach ($titles as $index => $title) {
                $code = $deptCode.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $designations[$code] = Designation::query()->updateOrCreate(
                    ['organization_id' => $orgId, 'code' => $code],
                    ['name' => $title, 'level' => $index + 1, 'is_active' => true],
                );
            }
        }

        $this->context['branch'] = $branch;
        $this->context['branchBlr'] = $branchBlr;
        $this->context['departments'] = $departments;
        $this->context['designations'] = $designations;
    }

    private function seedEmployees(): void
    {
        $orgId = $this->context['orgId'];
        $branch = $this->context['branch'];
        $departments = $this->context['departments'];
        $designations = $this->context['designations'];
        $owner = $this->context['owner'];

        $roster = $this->employeeRoster();
        $employeesByCode = [];
        $departmentHeads = [];

        foreach ($roster as $row) {
            $dept = $departments[$row['dept']];
            $designation = $designations[$row['designation']];

            $userId = null;
            if (! empty($row['user_email'])) {
                $user = User::query()->updateOrCreate(
                    ['email' => $row['user_email']],
                    [
                        'name' => $row['first_name'].' '.$row['last_name'],
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );

                $role = $row['role'] ?? 'employee';
                if (! $this->context['organization']->users()->whereKey($user->id)->exists()) {
                    $this->context['organization']->addMember($user, $role);
                }

                $userId = $user->id;
            }

            $employee = Employee::query()->updateOrCreate(
                ['organization_id' => $orgId, 'employee_code' => $row['code']],
                [
                    'user_id' => $userId,
                    'branch_id' => $branch->id,
                    'department_id' => $dept->id,
                    'designation_id' => $designation->id,
                    'reporting_manager_id' => null,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'gender' => $row['gender'] ?? null,
                    'date_of_birth' => $row['dob'] ?? null,
                    'email' => $row['email'],
                    'personal_email' => $row['personal_email'] ?? null,
                    'mobile' => $row['mobile'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'address_line_1' => $row['address'] ?? null,
                    'city' => $row['city'] ?? 'Mumbai',
                    'state' => $row['state'] ?? 'Maharashtra',
                    'postal_code' => $row['postal_code'] ?? null,
                    'country' => 'India',
                    'employment_type' => $row['employment_type'] ?? 'full_time',
                    'status' => $row['status'] ?? 'active',
                    'joining_date' => $row['joining_date'],
                    'probation_end_date' => $row['probation_end_date'] ?? null,
                ],
            );

            $employeesByCode[$row['code']] = $employee;

            if ($row['is_head'] ?? false) {
                $departmentHeads[$row['dept']] = $employee->id;
            }
        }

        foreach ($roster as $row) {
            if (($row['is_head'] ?? false) || $row['code'] === 'NE-001') {
                continue;
            }

            $headId = $departmentHeads[$row['dept']] ?? null;
            if ($headId && $headId !== $employeesByCode[$row['code']]->id) {
                $employeesByCode[$row['code']]->update(['reporting_manager_id' => $headId]);
            }
        }

        $employees = array_values($employeesByCode);
        $this->context['employees'] = collect($employees);
        $this->context['ownerEmployee'] = $employeesByCode['NE-001'] ?? null;

        if ($this->context['ownerEmployee'] && ! $this->context['ownerEmployee']->user_id) {
            $this->context['ownerEmployee']->update(['user_id' => $owner->id]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function employeeRoster(): array
    {
        $year = (int) now()->year;

        return [
            ['code' => 'NE-001', 'first_name' => 'Rajesh', 'last_name' => 'Mehta', 'dept' => 'ENG', 'designation' => 'ENG-01', 'email' => 'rajesh.mehta@novaenterprises.test', 'user_email' => self::OWNER_EMAIL, 'role' => 'organization-owner', 'gender' => 'male', 'dob' => '1978-03-14', 'joining_date' => '2015-04-01', 'mobile' => '+91 98765 43210', 'address' => '14 Marine Drive', 'postal_code' => '400020', 'is_head' => true],
            ['code' => 'NE-002', 'first_name' => 'Priya', 'last_name' => 'Sharma', 'dept' => 'ENG', 'designation' => 'ENG-02', 'email' => 'priya.sharma@novaenterprises.test', 'user_email' => 'priya.sharma@@NOVA_EMAIL_DOMAIN@@', 'role' => 'project-manager', 'gender' => 'female', 'dob' => "{$year}-07-05", 'joining_date' => '2018-06-15', 'mobile' => '+91 98765 43211', 'address' => '22 Bandra West', 'is_head' => true],
            ['code' => 'NE-003', 'first_name' => 'Arjun', 'last_name' => 'Kapoor', 'dept' => 'ENG', 'designation' => 'ENG-03', 'email' => 'arjun.kapoor@novaenterprises.test', 'gender' => 'male', 'dob' => '1990-11-22', 'joining_date' => '2019-01-10', 'mobile' => '+91 98765 43212'],
            ['code' => 'NE-004', 'first_name' => 'Sneha', 'last_name' => 'Iyer', 'dept' => 'ENG', 'designation' => 'ENG-04', 'email' => 'sneha.iyer@novaenterprises.test', 'gender' => 'female', 'dob' => '1993-05-08', 'joining_date' => '2020-08-03'],
            ['code' => 'NE-005', 'first_name' => 'Vikram', 'last_name' => 'Singh', 'dept' => 'ENG', 'designation' => 'ENG-05', 'email' => 'vikram.singh@novaenterprises.test', 'gender' => 'male', 'dob' => '1991-09-17', 'joining_date' => '2021-02-14'],
            ['code' => 'NE-006', 'first_name' => 'Ananya', 'last_name' => 'Desai', 'dept' => 'ENG', 'designation' => 'ENG-06', 'email' => 'ananya.desai@novaenterprises.test', 'gender' => 'female', 'dob' => '1994-01-30', 'joining_date' => '2022-07-18', 'status' => 'probation', 'probation_end_date' => now()->addMonths(2)->toDateString()],
            ['code' => 'NE-007', 'first_name' => 'Rohan', 'last_name' => 'Patil', 'dept' => 'ENG', 'designation' => 'ENG-04', 'email' => 'rohan.patil@novaenterprises.test', 'gender' => 'male', 'dob' => '1995-12-03', 'joining_date' => '2023-03-01'],
            ['code' => 'NE-008', 'first_name' => 'Kavita', 'last_name' => 'Nair', 'dept' => 'SAL', 'designation' => 'SAL-01', 'email' => 'kavita.nair@novaenterprises.test', 'user_email' => 'amit.verma@@NOVA_EMAIL_DOMAIN@@', 'role' => 'sales-manager', 'gender' => 'female', 'dob' => '1985-08-19', 'joining_date' => '2017-05-22', 'mobile' => '+91 98765 43220', 'is_head' => true],
            ['code' => 'NE-009', 'first_name' => 'Amit', 'last_name' => 'Verma', 'dept' => 'SAL', 'designation' => 'SAL-02', 'email' => 'amit.verma@novaenterprises.test', 'gender' => 'male', 'dob' => "{$year}-07-12", 'joining_date' => '2019-09-09', 'mobile' => '+91 98765 43221'],
            ['code' => 'NE-010', 'first_name' => 'Divya', 'last_name' => 'Reddy', 'dept' => 'SAL', 'designation' => 'SAL-03', 'email' => 'divya.reddy@novaenterprises.test', 'gender' => 'female', 'dob' => '1992-04-25', 'joining_date' => '2020-11-16'],
            ['code' => 'NE-011', 'first_name' => 'Sanjay', 'last_name' => 'Malhotra', 'dept' => 'SAL', 'designation' => 'SAL-03', 'email' => 'sanjay.malhotra@novaenterprises.test', 'gender' => 'male', 'dob' => '1990-06-11', 'joining_date' => '2021-04-05'],
            ['code' => 'NE-012', 'first_name' => 'Neha', 'last_name' => 'Gupta', 'dept' => 'HR', 'designation' => 'HR-01', 'email' => 'neha.gupta@novaenterprises.test', 'user_email' => 'neha.gupta@@NOVA_EMAIL_DOMAIN@@', 'role' => 'hr', 'gender' => 'female', 'dob' => '1987-02-28', 'joining_date' => '2016-08-01', 'mobile' => '+91 98765 43230', 'is_head' => true],
            ['code' => 'NE-013', 'first_name' => 'Pooja', 'last_name' => 'Saxena', 'dept' => 'HR', 'designation' => 'HR-02', 'email' => 'pooja.saxena@novaenterprises.test', 'gender' => 'female', 'dob' => "{$year}-07-18", 'joining_date' => '2019-03-12'],
            ['code' => 'NE-014', 'first_name' => 'Rahul', 'last_name' => 'Joshi', 'dept' => 'HR', 'designation' => 'HR-03', 'email' => 'rahul.joshi@novaenterprises.test', 'gender' => 'male', 'dob' => '1991-10-05', 'joining_date' => '2022-01-17'],
            ['code' => 'NE-015', 'first_name' => 'Meera', 'last_name' => 'Krishnan', 'dept' => 'HR', 'designation' => 'HR-04', 'email' => 'meera.krishnan@novaenterprises.test', 'gender' => 'female', 'dob' => '1989-12-20', 'joining_date' => '2020-06-08'],
            ['code' => 'NE-016', 'first_name' => 'Suresh', 'last_name' => 'Pillai', 'dept' => 'FIN', 'designation' => 'FIN-01', 'email' => 'suresh.pillai@novaenterprises.test', 'gender' => 'male', 'dob' => '1983-07-07', 'joining_date' => '2014-11-03', 'mobile' => '+91 98765 43240', 'is_head' => true],
            ['code' => 'NE-017', 'first_name' => 'Lakshmi', 'last_name' => 'Menon', 'dept' => 'FIN', 'designation' => 'FIN-02', 'email' => 'lakshmi.menon@novaenterprises.test', 'gender' => 'female', 'dob' => '1990-03-15', 'joining_date' => '2018-02-19'],
            ['code' => 'NE-018', 'first_name' => 'Deepak', 'last_name' => 'Choudhary', 'dept' => 'FIN', 'designation' => 'FIN-03', 'email' => 'deepak.choudhary@novaenterprises.test', 'gender' => 'male', 'dob' => '1992-08-30', 'joining_date' => '2020-09-14'],
            ['code' => 'NE-019', 'first_name' => 'Anjali', 'last_name' => 'Bhat', 'dept' => 'FIN', 'designation' => 'FIN-04', 'email' => 'anjali.bhat@novaenterprises.test', 'gender' => 'female', 'dob' => '1994-05-02', 'joining_date' => '2023-07-03'],
            ['code' => 'NE-020', 'first_name' => 'Harish', 'last_name' => 'Kulkarni', 'dept' => 'OPS', 'designation' => 'OPS-01', 'email' => 'harish.kulkarni@novaenterprises.test', 'gender' => 'male', 'dob' => '1986-01-18', 'joining_date' => '2015-12-07', 'mobile' => '+91 98765 43250', 'is_head' => true],
            ['code' => 'NE-021', 'first_name' => 'Tanvi', 'last_name' => 'Shah', 'dept' => 'OPS', 'designation' => 'OPS-02', 'email' => 'tanvi.shah@novaenterprises.test', 'gender' => 'female', 'dob' => "{$year}-07-24", 'joining_date' => '2019-10-21'],
            ['code' => 'NE-022', 'first_name' => 'Manish', 'last_name' => 'Agarwal', 'dept' => 'OPS', 'designation' => 'OPS-02', 'email' => 'manish.agarwal@novaenterprises.test', 'gender' => 'male', 'dob' => '1991-04-09', 'joining_date' => '2021-08-30'],
            ['code' => 'NE-023', 'first_name' => 'Isha', 'last_name' => 'Chopra', 'dept' => 'OPS', 'designation' => 'OPS-03', 'email' => 'isha.chopra@novaenterprises.test', 'gender' => 'female', 'dob' => '1993-11-27', 'joining_date' => '2022-05-16'],
            ['code' => 'NE-024', 'first_name' => 'Gaurav', 'last_name' => 'Tiwari', 'dept' => 'ENG', 'designation' => 'ENG-03', 'email' => 'gaurav.tiwari@novaenterprises.test', 'gender' => 'male', 'dob' => '1988-06-06', 'joining_date' => '2017-01-23'],
            ['code' => 'NE-025', 'first_name' => 'Shreya', 'last_name' => 'Banerjee', 'dept' => 'SAL', 'designation' => 'SAL-04', 'email' => 'shreya.banerjee@novaenterprises.test', 'gender' => 'female', 'dob' => "{$year}-07-30", 'joining_date' => '2023-11-06'],
            ['code' => 'NE-026', 'first_name' => 'Aditya', 'last_name' => 'Rao', 'dept' => 'ENG', 'designation' => 'ENG-04', 'email' => 'aditya.rao@novaenterprises.test', 'gender' => 'male', 'dob' => '1996-02-14', 'joining_date' => '2024-02-01', 'status' => 'probation', 'probation_end_date' => now()->addMonth()->toDateString()],
            ['code' => 'NE-027', 'first_name' => 'Nisha', 'last_name' => 'Khanna', 'dept' => 'FIN', 'designation' => 'FIN-02', 'email' => 'nisha.khanna@novaenterprises.test', 'gender' => 'female', 'dob' => '1995-09-23', 'joining_date' => '2024-06-10'],
        ];
    }

    private function seedShiftsAndAttendance(): void
    {
        $orgId = $this->context['orgId'];
        $employees = $this->context['employees'];

        $shifts = [];
        foreach ([
            ['name' => 'General Shift', 'code' => 'GEN', 'start' => '09:00', 'end' => '18:00'],
            ['name' => 'Morning Shift', 'code' => 'MOR', 'start' => '06:00', 'end' => '15:00'],
            ['name' => 'Flexible Shift', 'code' => 'FLX', 'start' => '10:00', 'end' => '19:00'],
        ] as $preset) {
            $shifts[$preset['code']] = HrmsShift::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $preset['code']],
                [
                    'name' => $preset['name'],
                    'start_time' => $preset['start'],
                    'end_time' => $preset['end'],
                    'break_minutes' => 60,
                    'grace_period_minutes' => 15,
                    'working_hours' => 8,
                    'minimum_working_minutes' => 420,
                    'overtime_threshold_minutes' => 480,
                    'is_overnight' => false,
                    'is_active' => true,
                ],
            );
        }

        $defaultShift = $shifts['GEN'];

        foreach ($employees as $index => $employee) {
            if (! in_array($employee->status, ['active', 'probation'], true)) {
                continue;
            }

            EmployeeShiftAssignment::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'employee_id' => $employee->id,
                    'shift_id' => $defaultShift->id,
                    'effective_from' => $employee->joining_date?->toDateString() ?? now()->subYear()->toDateString(),
                ],
                ['effective_to' => null],
            );
        }

        $startOfMonth = now()->startOfMonth();
        $today = now()->startOfDay();
        $statusCycle = ['present', 'present', 'present', 'late', 'present', 'absent', 'on_leave'];

        foreach ($employees->take(22) as $employeeIndex => $employee) {
            $cursor = $startOfMonth->copy();

            while ($cursor->lte($today)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();

                    continue;
                }

                $status = $statusCycle[($employeeIndex + $cursor->day) % count($statusCycle)];
                $dateStr = $cursor->toDateString();

                $clockIn = $cursor->copy()->setTime(9, 0);
                $clockOut = $cursor->copy()->setTime(18, 0);
                $lateMinutes = 0;
                $workingMinutes = 480;

                if ($status === 'late') {
                    $clockIn = $cursor->copy()->setTime(9, 35);
                    $lateMinutes = 35;
                    $workingMinutes = 445;
                }

                if (in_array($status, ['absent', 'on_leave'], true)) {
                    AttendanceRecord::query()->updateOrCreate(
                        [
                            'organization_id' => $orgId,
                            'employee_id' => $employee->id,
                            'attendance_date' => $dateStr,
                        ],
                        [
                            'shift_id' => $defaultShift->id,
                            'status' => $status,
                            'source' => 'manual',
                            'clock_in_at' => null,
                            'clock_out_at' => null,
                            'working_minutes' => 0,
                            'late_minutes' => 0,
                        ],
                    );
                } else {
                    AttendanceRecord::query()->updateOrCreate(
                        [
                            'organization_id' => $orgId,
                            'employee_id' => $employee->id,
                            'attendance_date' => $dateStr,
                        ],
                        [
                            'shift_id' => $defaultShift->id,
                            'status' => $status,
                            'source' => $employeeIndex % 3 === 0 ? 'biometric' : 'manual',
                            'clock_in_at' => $clockIn,
                            'clock_out_at' => $clockOut,
                            'working_minutes' => $workingMinutes,
                            'late_minutes' => $lateMinutes,
                            'early_departure_minutes' => 0,
                            'overtime_minutes' => $employeeIndex % 7 === 0 ? 45 : 0,
                        ],
                    );
                }

                $cursor->addDay();
            }
        }

        $this->context['shifts'] = $shifts;
    }

    private function seedLeaveData(): void
    {
        $orgId = $this->context['orgId'];
        $employees = $this->context['employees'];

        $leaveTypes = [];
        foreach (config('hrms.default_leave_types', []) as $key => $defaults) {
            $leaveTypes[$key] = LeaveType::query()->updateOrCreate(
                ['organization_id' => $orgId, 'code' => $defaults['code']],
                [
                    'name' => $defaults['name'],
                    'is_paid' => $defaults['is_paid'],
                    'requires_approval' => $defaults['requires_approval'],
                    'requires_hr_approval' => $defaults['requires_hr_approval'],
                    'allow_half_day' => $defaults['allow_half_day'],
                    'max_days_per_year' => $defaults['max_days_per_year'],
                    'allocation_days' => $defaults['max_days_per_year'],
                    'is_active' => true,
                ],
            );
        }

        foreach ($employees->take(20) as $employee) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalance::query()->updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => (int) now()->year,
                    ],
                    [
                        'entitled' => $leaveType->allocation_days ?? 12,
                        'used' => fake()->numberBetween(0, 4),
                        'pending' => fake()->numberBetween(0, 2),
                        'balance' => ($leaveType->allocation_days ?? 12) - fake()->numberBetween(0, 4),
                    ],
                );
            }
        }

        $pendingEmployees = $employees->slice(3, 5)->values();
        $leaveReasons = ['Family function', 'Medical appointment', 'Personal travel', 'Home renovation', 'Child school event'];
        foreach ($pendingEmployees as $index => $employee) {
            $start = now()->addDays(7 + ($index * 3))->startOfDay();
            LeaveApplication::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'employee_id' => $employee->id,
                    'start_date' => $start->toDateString(),
                    'leave_type_id' => $leaveTypes['annual']->id,
                ],
                [
                    'end_date' => $start->copy()->addDays(2)->toDateString(),
                    'is_half_day' => false,
                    'days' => 3,
                    'reason' => $leaveReasons[$index % count($leaveReasons)],
                    'status' => 'pending',
                ],
            );
        }

        $this->context['leaveTypes'] = $leaveTypes;
    }

    private function seedAnnouncements(): void
    {
        $orgId = $this->context['orgId'];
        $owner = $this->context['owner'];

        $announcements = [
            [
                'title' => 'Q3 Town Hall — Mumbai HQ, 31 July',
                'body' => 'Join leadership for product roadmap updates, customer wins, and an open Q&A. All employees are welcome in the auditorium or via the live stream.',
            ],
            [
                'title' => 'Revised Hybrid Work Policy Effective 1 August',
                'body' => 'Engineering and Operations teams may work remotely up to two days per week with manager approval. Please review the updated policy in the HR portal.',
            ],
            [
                'title' => 'Wellness Week: Free Health Check-ups',
                'body' => 'Occupational health screenings will run 28 July – 1 August on the 4th floor. Book a 20-minute slot through ESS.',
            ],
        ];

        foreach ($announcements as $item) {
            HrmsAnnouncement::query()->updateOrCreate(
                ['organization_id' => $orgId, 'title' => $item['title']],
                [
                    'body' => $item['body'],
                    'target_audience' => 'everyone',
                    'start_date' => now()->subDays(2)->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'is_active' => true,
                    'created_by' => $owner->id,
                ],
            );
        }
    }

    private function seedAssetsAndDocuments(): void
    {
        $orgId = $this->context['orgId'];
        $employees = $this->context['employees'];

        $assetCatalog = [
            ['name' => 'MacBook Pro 14"', 'category' => 'laptop', 'code' => 'AST-LAP-001'],
            ['name' => 'Dell Latitude 5540', 'category' => 'laptop', 'code' => 'AST-LAP-002'],
            ['name' => 'iPhone 15', 'category' => 'mobile', 'code' => 'AST-MOB-001'],
            ['name' => 'Dell UltraSharp 27"', 'category' => 'monitor', 'code' => 'AST-MON-001'],
            ['name' => 'Lenovo ThinkPad X1', 'category' => 'laptop', 'code' => 'AST-LAP-003'],
            ['name' => 'Jabra Evolve2 Headset', 'category' => 'accessory', 'code' => 'AST-ACC-001'],
        ];

        foreach ($assetCatalog as $index => $assetData) {
            $assignee = $employees[$index] ?? $employees->first();

            $asset = EmployeeAsset::query()->updateOrCreate(
                ['organization_id' => $orgId, 'asset_code' => $assetData['code']],
                [
                    'name' => $assetData['name'],
                    'category' => $assetData['category'],
                    'serial_number' => 'SN-'.strtoupper(Str::random(8)),
                    'status' => 'assigned',
                    'employee_id' => $assignee->id,
                    'assigned_date' => now()->subMonths(3)->toDateString(),
                ],
            );

            EmployeeAssetAssignment::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'employee_asset_id' => $asset->id,
                    'employee_id' => $assignee->id,
                ],
                ['assigned_date' => now()->subMonths(3)->toDateString()],
            );
        }

        $documentTemplates = [
            ['title' => 'Passport Copy', 'category' => 'passport', 'expires_at' => now()->addDays(12)],
            ['title' => 'Employment Contract', 'category' => 'appointment_letter', 'expires_at' => null],
            ['title' => 'PAN Card', 'category' => 'pan', 'expires_at' => null],
            ['title' => 'Work Visa', 'category' => 'other', 'expires_at' => now()->addDays(21)],
            ['title' => 'Background Verification Report', 'category' => 'other', 'expires_at' => now()->addDays(5)],
        ];

        foreach ($employees->take(12) as $index => $employee) {
            $template = $documentTemplates[$index % count($documentTemplates)];

            EmployeeDocument::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'employee_id' => $employee->id,
                    'title' => $template['title'],
                ],
                [
                    'category' => $template['category'],
                    'expires_at' => $template['expires_at'],
                    'verification_status' => $index % 4 === 0 ? 'verified' : 'pending',
                ],
            );
        }
    }

    private function seedRecruitment(): void
    {
        $orgId = $this->context['orgId'];
        $owner = $this->context['owner'];
        $departments = $this->context['departments'];
        $designations = $this->context['designations'];

        $jobTitles = [
            ['title' => 'Senior Backend Engineer', 'dept' => 'ENG', 'designation' => 'ENG-03'],
            ['title' => 'Frontend Developer', 'dept' => 'ENG', 'designation' => 'ENG-04'],
            ['title' => 'DevOps Engineer', 'dept' => 'ENG', 'designation' => 'ENG-06'],
            ['title' => 'Enterprise Account Executive', 'dept' => 'SAL', 'designation' => 'SAL-03'],
            ['title' => 'HR Business Partner', 'dept' => 'HR', 'designation' => 'HR-04'],
            ['title' => 'Financial Analyst', 'dept' => 'FIN', 'designation' => 'FIN-02'],
            ['title' => 'QA Automation Engineer', 'dept' => 'ENG', 'designation' => 'ENG-05'],
            ['title' => 'Customer Success Manager', 'dept' => 'SAL', 'designation' => 'SAL-02'],
            ['title' => 'Talent Acquisition Specialist', 'dept' => 'HR', 'designation' => 'HR-03'],
        ];

        $openings = [];
        foreach ($jobTitles as $index => $job) {
            $dept = $departments[$job['dept']];
            $designation = $designations[$job['designation']];

            $requisition = JobRequisition::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'department_id' => $dept->id,
                    'designation_id' => $designation->id,
                    'business_justification' => 'Headcount for '.$job['title'].' to support FY growth plan.',
                ],
                [
                    'employment_type' => 'full_time',
                    'number_of_positions' => 1,
                    'status' => 'approved',
                ],
            );

            $openings[] = JobOpening::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'title' => $job['title'],
                    'job_requisition_id' => $requisition->id,
                ],
                [
                    'department_id' => $dept->id,
                    'designation_id' => $designation->id,
                    'employment_type' => 'full_time',
                    'location' => 'Mumbai, India',
                    'description' => 'We are hiring a '.$job['title'].' to join our growing '.$dept->name.' team.',
                    'status' => $index < 6 ? 'published' : 'draft',
                    'publish_date' => $index < 6 ? now()->subDays(10 - $index)->toDateString() : null,
                    'closing_date' => now()->addMonths(2)->toDateString(),
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }

        $stages = InterviewStage::query()
            ->where('organization_id', $orgId)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug');

        $applicationStages = array_keys(config('hrms.recruitment.application_stages', []));
        $candidates = [];
        $candidateNames = [
            ['Ravi', 'Menon'], ['Kiran', 'Subramanian'], ['Aisha', 'Khan'], ['Varun', 'Desai'],
            ['Pallavi', 'Rao'], ['Nikhil', 'Bose'], ['Simran', 'Kaur'], ['Karthik', 'Venkatesh'],
            ['Fatima', 'Sheikh'], ['Abhishek', 'Dutta'], ['Leela', 'Prasad'], ['Mohit', 'Sarin'],
            ['Anita', 'Thomas'], ['Yusuf', 'Mirza'], ['Charu', 'Oberoi'], ['Harsh', 'Bhatt'],
            ['Ritika', 'Pandey'], ['Farhan', 'Ali'], ['Swati', 'Mishra'], ['Dev', 'Chakraborty'],
            ['Komal', 'Sethi'], ['Tarun', 'Goyal'],
        ];

        foreach ($candidateNames as $index => [$first, $last]) {
            $email = strtolower($first.'.'.$last).'@candidates.test';
            $candidates[] = Candidate::query()->updateOrCreate(
                ['organization_id' => $orgId, 'email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => '+91 9'.str_pad((string) (800000000 + $index), 9, '0', STR_PAD_LEFT),
                    'source' => ['direct', 'referral', 'linkedin', 'job_board'][$index % 4],
                ],
            );
        }

        $applications = [];
        foreach ($candidates as $index => $candidate) {
            $opening = $openings[$index % count($openings)];
            $stage = $applicationStages[min($index % count($applicationStages), count($applicationStages) - 1)];

            $applications[] = JobApplication::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'candidate_id' => $candidate->id,
                    'job_opening_id' => $opening->id,
                ],
                [
                    'stage' => $stage,
                    'status' => in_array($stage, ['rejected', 'withdrawn', 'hired'], true) ? 'closed' : 'active',
                    'applied_date' => now()->subDays(25 - ($index % 20))->toDateString(),
                    'source' => $candidate->source,
                    'assigned_recruiter_id' => $owner->id,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );
        }

        $screeningStage = $stages->get('screening') ?? $stages->first();
        $technicalStage = $stages->get('technical_interview') ?? $stages->get('interview') ?? $screeningStage;

        foreach (array_slice($applications, 0, 8) as $index => $application) {
            InterviewRound::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'job_application_id' => $application->id,
                    'round_number' => 1,
                ],
                [
                    'interview_stage_id' => $index % 2 === 0 ? $screeningStage?->id : $technicalStage?->id,
                    'interview_type' => $index % 3 === 0 ? 'in_person' : 'video',
                    'scheduled_at' => now()->addDays(2 + $index)->setTime(11, 0),
                    'duration_minutes' => 60,
                    'location' => $index % 3 === 0 ? 'Mumbai HQ — Room 4A' : 'Google Meet',
                    'status' => 'scheduled',
                ],
            );
        }

        $offerTemplate = OfferTemplate::query()->updateOrCreate(
            ['organization_id' => $orgId, 'name' => 'Standard Full-Time Offer'],
            [
                'department_id' => $departments['ENG']->id,
                'employment_type' => 'full_time',
                'is_active' => true,
                'template_content' => 'Dear {{candidate_name}}, Nova Enterprises is pleased to offer you the position of {{position}}.',
            ],
        );

        foreach (array_slice($applications, 15, 3) as $application) {
            OfferLetter::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'job_application_id' => $application->id,
                ],
                [
                    'candidate_id' => $application->candidate_id,
                    'offer_template_id' => $offerTemplate->id,
                    'proposed_salary' => fake()->randomFloat(2, 900000, 2400000),
                    'variable_pay' => fake()->randomFloat(2, 50000, 300000),
                    'benefits' => 'Health insurance, gratuity, flexible benefits',
                    'joining_date' => now()->addMonth()->toDateString(),
                    'expiry_date' => now()->addMonths(2)->toDateString(),
                    'status' => 'sent',
                    'generated_content' => 'Offer letter for '.$application->candidate?->fullName(),
                ],
            );
        }
    }

    private function seedProjectsAndTasks(): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $employees = $this->context['employees'];

        $projectService = app(ProjectService::class);
        $taskService = app(TaskService::class);

        $statuses = TaskStatus::query()->where('organization_id', $organization->id)->get()->keyBy('slug');
        $priorities = TaskPriority::query()->where('organization_id', $organization->id)->get()->keyBy('slug');

        $projectDefinitions = [
            ['name' => 'NovaCloud Migration', 'priority' => 'high', 'completion' => 62, 'budget' => 8500000],
            ['name' => 'CRM Mobile App v2', 'priority' => 'high', 'completion' => 38, 'budget' => 4200000],
            ['name' => 'Enterprise SSO Rollout', 'priority' => 'medium', 'completion' => 81, 'budget' => 1800000],
            ['name' => 'Data Warehouse Modernization', 'priority' => 'medium', 'completion' => 24, 'budget' => 6500000],
            ['name' => 'Customer Portal Redesign', 'priority' => 'low', 'completion' => 55, 'budget' => 2900000],
            ['name' => 'Sales Pipeline Automation', 'priority' => 'medium', 'completion' => 47, 'budget' => 2100000],
        ];

        $projects = [];
        foreach ($projectDefinitions as $definition) {
            $project = Project::query()->where('organization_id', $organization->id)
                ->where('name', $definition['name'])
                ->first();

            if ($project === null) {
                $project = $projectService->create([
                    'organization_id' => $organization->id,
                    'name' => $definition['name'],
                    'description' => 'Strategic initiative: '.$definition['name'],
                    'owner_id' => $owner->id,
                    'manager_id' => $owner->id,
                    'priority' => $definition['priority'],
                    'start_date' => now()->subMonths(2)->toDateString(),
                    'planned_end_date' => now()->addMonths(4)->toDateString(),
                    'estimated_budget' => $definition['budget'],
                    'completion_percentage' => $definition['completion'],
                ], $owner);
            } else {
                $project->update([
                    'completion_percentage' => $definition['completion'],
                    'estimated_budget' => $definition['budget'],
                ]);
            }

            ProjectBudget::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'project_id' => $project->id, 'name' => 'Primary Budget'],
                [
                    'currency' => 'INR',
                    'planned_total' => $definition['budget'],
                    'actual_total' => round($definition['budget'] * ($definition['completion'] / 100) * 0.85, 2),
                    'forecast_total' => round($definition['budget'] * 1.05, 2),
                    'variance_total' => round($definition['budget'] * 0.05, 2),
                    'status' => 'active',
                ],
            );

            foreach ([
                ['name' => 'Discovery & Planning', 'status' => 'completed'],
                ['name' => 'Implementation', 'status' => 'in_progress'],
                ['name' => 'UAT & Go-Live', 'status' => 'pending'],
            ] as $sequence => $milestone) {
                ProjectMilestone::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'project_id' => $project->id,
                        'name' => $milestone['name'],
                    ],
                    [
                        'sequence' => $sequence + 1,
                        'due_date' => now()->addMonths($sequence + 1)->toDateString(),
                        'status' => $milestone['status'],
                        'completed_at' => $milestone['status'] === 'completed' ? now()->subWeeks(2) : null,
                    ],
                );
            }

            $memberUsers = $employees->filter(fn (Employee $e) => $e->user_id !== null)->take(3);
            foreach ($memberUsers as $memberEmployee) {
                ProjectMember::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'project_id' => $project->id,
                        'user_id' => $memberEmployee->user_id,
                    ],
                    [
                        'project_role' => 'contributor',
                        'joined_at' => now()->subMonth(),
                        'is_active' => true,
                    ],
                );
            }

            $projects[] = $project;
        }

        $taskTitles = [
            'Finalize API contract with integration partner',
            'Configure staging environment load balancer',
            'Review security audit findings',
            'Update sprint board for release 2.4',
            'Draft customer communication for downtime window',
            'Migrate legacy user permissions',
            'Prepare executive steering deck',
            'Validate data migration reconciliation report',
            'Fix regression in invoice PDF export',
            'Align UX copy with marketing team',
            'Set up monitoring alerts for payment gateway',
            'Document rollback procedure',
            'Complete vendor due diligence checklist',
            'Refactor notification service queue handlers',
            'Create test cases for leave accrual edge cases',
            'Schedule cross-team dependency review',
            'Implement role-based dashboard widgets',
            'Optimize SQL queries on reporting module',
            'Publish release notes for mobile build 204',
            'Conduct stakeholder sign-off workshop',
            'Resolve blocked dependency on SSO metadata',
            'Add audit logging to bulk import pipeline',
            'Review capacity plan for Q3 deliverables',
            'Update project RAID log entries',
            'Prepare demo environment seed scripts',
            'Validate CRM lead assignment rules',
            'Finalize quotation approval workflow',
            'Complete code review for payroll export',
            'Sync resource allocations with project plan',
            'Close out completed milestone action items',
            'Draft training materials for HR module',
            'Verify backup restore runbook',
        ];

        $statusSlugs = ['to-do', 'in-progress', 'review', 'blocked', 'completed', 'in-progress', 'to-do'];
        $prioritySlugs = ['low', 'medium', 'high', 'critical', 'medium', 'high', 'low'];
        $assignees = $employees->filter(fn (Employee $e) => $e->user_id !== null)->values();

        $taskCount = 0;
        foreach ($projects as $projectIndex => $project) {
            $batch = array_slice($taskTitles, $projectIndex * 5, 5);
            foreach ($batch as $titleIndex => $title) {
                $statusSlug = $statusSlugs[($projectIndex + $titleIndex) % count($statusSlugs)];
                $prioritySlug = $prioritySlugs[($projectIndex + $titleIndex) % count($prioritySlugs)];
                $assignee = $assignees[($projectIndex + $titleIndex) % max($assignees->count(), 1)] ?? null;

                $existing = Task::query()
                    ->where('organization_id', $organization->id)
                    ->where('project_id', $project->id)
                    ->where('title', $title)
                    ->first();

                if ($existing) {
                    continue;
                }

                $task = $taskService->createWorkManagement([
                    'organization_id' => $organization->id,
                    'project_id' => $project->id,
                    'title' => $title,
                    'status_id' => $statuses[$statusSlug]?->id,
                    'priority_id' => $priorities[$prioritySlug]?->id,
                    'assigned_to' => $assignee?->user_id,
                    'due_date' => now()->addDays(3 + $titleIndex)->toDateString(),
                    'completion_percentage' => $statusSlug === 'completed' ? 100 : fake()->numberBetween(0, 80),
                ], $owner);

                if ($taskCount % 5 === 0) {
                    TaskComment::query()->create([
                        'organization_id' => $organization->id,
                        'task_id' => $task->id,
                        'user_id' => $owner->id,
                        'comment' => 'Please prioritize this for the upcoming steering review.',
                    ]);
                }

                $taskCount++;
            }
        }

        $this->context['projects'] = collect($projects);
    }

    private function seedResourcePlanning(): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $employees = $this->context['employees'];
        $projects = $this->context['projects'] ?? collect();

        foreach ($employees->take(15) as $index => $employee) {
            $project = $projects[$index % max($projects->count(), 1)] ?? null;

            ResourceAllocation::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                    'project_id' => $project?->id,
                    'planned_start_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'allocation_type' => $project ? 'project' : 'internal',
                    'allocation_percentage' => [25, 50, 75, 100][$index % 4],
                    'planned_end_date' => now()->endOfMonth()->toDateString(),
                    'notes' => 'Q3 capacity plan allocation',
                    'created_by' => $owner->id,
                ],
            );

            WorkloadSnapshot::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                    'snapshot_date' => now()->toDateString(),
                ],
                [
                    'allocated_hours' => fake()->randomFloat(2, 4, 9),
                    'available_hours' => 8,
                    'utilization_percentage' => fake()->randomFloat(2, 55, 115),
                    'overall_status' => ['optimal', 'underutilized', 'overallocated'][$index % 3],
                ],
            );
        }
    }

    private function seedCrmData(): void
    {
        $organization = $this->context['organization'];
        $owner = $this->context['owner'];

        $leadStatuses = array_keys(config('leads.statuses', []));
        $leadCompanies = [
            'Tata Digital', 'Reliance Retail', 'Infosys BPM', 'HDFC Ergo', 'Mahindra Logistics',
            'L&T Technology Services', 'Asian Paints', 'Bajaj Finserv', 'Godrej Properties', 'Zomato Enterprise',
            'Swiggy Instamart', 'Nykaa B2B', 'Razorpay', 'Freshworks', 'PhonePe Business',
            'Ola Electric Fleet', 'Byju\'s Corporate', 'MakeMyTrip Corporate', 'ICICI Lombard', 'Wipro GCC',
        ];

        $leads = [];
        foreach ($leadCompanies as $index => $company) {
            $leads[] = Lead::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'company' => $company,
                ],
                [
                    'name' => fake()->name(),
                    'email' => Str::slug($company).'@prospect.test',
                    'phone' => '+91 98'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                    'source' => ['website', 'referral', 'google_ads', 'linkedin'][$index % 4],
                    'industry' => ['Technology', 'Finance', 'Retail', 'Manufacturing'][$index % 4],
                    'budget' => fake()->randomFloat(2, 250000, 5000000),
                    'priority' => ['low', 'medium', 'high'][$index % 3],
                    'status' => $leadStatuses[$index % count($leadStatuses)],
                    'tags' => $index % 3 === 0 ? ['enterprise', 'hot'] : ['follow-up'],
                    'created_by' => $owner->id,
                ],
            );
        }

        $customers = [];
        $customerCompanies = [
            'BlueStar Industries', 'Sunrise Healthcare', 'Vertex Manufacturing', 'Pinnacle Finance',
            'Coastal Retail Group', 'Summit Education', 'Horizon Logistics', 'Apex Consulting',
        ];

        foreach ($customerCompanies as $index => $company) {
            $customers[] = Customer::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'company' => $company,
                ],
                [
                    'name' => fake()->name(),
                    'email' => 'accounts@'.Str::slug($company).'.test',
                    'phone' => '+91 22 4'.str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                    'website' => 'https://'.Str::slug($company).'.test',
                    'industry' => ['Technology', 'Healthcare', 'Finance', 'Retail'][$index % 4],
                    'status' => ['active', 'active', 'active', 'inactive'][$index % 4],
                    'city' => ['Mumbai', 'Bengaluru', 'Pune', 'Hyderabad'][$index % 4],
                    'country' => 'India',
                    'tags' => $index % 2 === 0 ? ['vip', 'recurring'] : ['enterprise'],
                    'created_by' => $owner->id,
                ],
            );
        }

        $products = [];
        foreach ([
            ['name' => 'Konnect Nex Enterprise License', 'sku' => 'NOVA-CRM-ENT', 'type' => 'service', 'price' => 149999],
            ['name' => 'Implementation Package', 'sku' => 'NOVA-IMPL-STD', 'type' => 'service', 'price' => 350000],
            ['name' => 'Premium Support (Annual)', 'sku' => 'NOVA-SUP-PREM', 'type' => 'service', 'price' => 85000],
        ] as $productData) {
            $products[] = Product::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'sku' => $productData['sku']],
                [
                    'name' => $productData['name'],
                    'description' => $productData['name'].' for mid-market and enterprise customers.',
                    'type' => $productData['type'],
                    'unit_price' => $productData['price'],
                    'currency' => 'INR',
                    'unit' => 'unit',
                    'tax_rate' => 18,
                    'category' => 'Software',
                    'status' => 'active',
                    'created_by' => $owner->id,
                ],
            );
        }

        $pipelineStages = array_keys(config('pipeline.stages', []));
        $opportunities = [];
        foreach (array_slice($customers, 0, 5) as $index => $customer) {
            $opportunities[] = Opportunity::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $customer->company.' — Platform Expansion',
                ],
                [
                    'customer_id' => $customer->id,
                    'lead_id' => $leads[$index]->id ?? null,
                    'stage' => $pipelineStages[$index % count($pipelineStages)],
                    'amount' => fake()->randomFloat(2, 500000, 4500000),
                    'currency' => 'INR',
                    'probability' => [20, 40, 60, 75, 90][$index],
                    'expected_close_date' => now()->addMonths(2 + $index)->toDateString(),
                    'assigned_to' => $owner->id,
                    'created_by' => $owner->id,
                ],
            );
        }

        foreach (array_slice($customers, 0, 3) as $index => $customer) {
            $subtotal = 350000 + ($index * 125000);
            $discount = round($subtotal * 0.05, 2);
            $tax = round(($subtotal - $discount) * 0.18, 2);
            $total = $subtotal - $discount + $tax;

            $quotation = Quotation::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'number' => 'QUO-'.now()->format('Y').'-'.str_pad((string) (1001 + $index), 4, '0', STR_PAD_LEFT),
                ],
                [
                    'customer_id' => $customer->id,
                    'opportunity_id' => $opportunities[$index]->id ?? null,
                    'title' => 'Proposal for '.$customer->company,
                    'status' => ['draft', 'sent', 'accepted'][$index],
                    'issue_date' => now()->subDays(10 - $index)->toDateString(),
                    'valid_until' => now()->addDays(30)->toDateString(),
                    'currency' => 'INR',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_total' => $tax,
                    'total' => $total,
                    'created_by' => $owner->id,
                ],
            );

            QuotationItem::query()->updateOrCreate(
                [
                    'quotation_id' => $quotation->id,
                    'product_id' => $products[$index % count($products)]->id,
                ],
                [
                    'description' => $products[$index % count($products)]->name,
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'tax_rate' => 18,
                    'discount_percent' => 5,
                    'line_total' => $total,
                    'sort_order' => 1,
                ],
            );
        }

        $invoiceCustomer = $customers[0];
        $invoiceSubtotal = 584900;
        $invoiceDiscount = round($invoiceSubtotal * 0.05, 2);
        $invoiceTax = round(($invoiceSubtotal - $invoiceDiscount) * 0.18, 2);
        $invoiceTotal = $invoiceSubtotal - $invoiceDiscount + $invoiceTax;

        $invoice = Invoice::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'number' => 'INV-'.now()->format('Y').'-2001',
            ],
            [
                'customer_id' => $invoiceCustomer->id,
                'opportunity_id' => $opportunities[0]->id ?? null,
                'title' => 'Konnect Nex Enterprise — Year 1',
                'status' => 'partially_paid',
                'issue_date' => now()->subDays(15)->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'currency' => 'INR',
                'subtotal' => $invoiceSubtotal,
                'discount_amount' => $invoiceDiscount,
                'tax_total' => $invoiceTax,
                'total' => $invoiceTotal,
                'amount_paid' => round($invoiceTotal * 0.5, 2),
                'created_by' => $owner->id,
            ],
        );

        Payment::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'invoice_id' => $invoice->id,
                'number' => 'PAY-'.now()->format('Y').'-3001',
            ],
            [
                'customer_id' => $invoiceCustomer->id,
                'amount' => round($invoiceTotal * 0.5, 2),
                'currency' => 'INR',
                'payment_date' => now()->subDays(5)->toDateString(),
                'method' => 'bank_transfer',
                'reference' => 'UTR-NOVA-24072401',
                'recorded_by' => $owner->id,
            ],
        );
    }

    private function seedMarketingData(): void
    {
        if (! Schema::hasTable('marketing_campaigns')) {
            return;
        }

        $organization = $this->context['organization'];
        $owner = $this->context['owner'];
        $orgId = $organization->id;

        $campaigns = [
            [
                'name' => 'Q3 Enterprise Demand Gen',
                'slug' => 'q3-enterprise-demand-gen',
                'status' => MarketingCampaign::STATUS_ACTIVE,
                'description' => 'LinkedIn + Google Ads pipeline for mid-market SaaS buyers.',
                'budget_amount' => 850000,
                'channels' => ['google_ads', 'linkedin', 'email'],
                'audience' => ['persona' => 'VP Sales / COO', 'size' => '50-500'],
                'utm_campaign' => 'q3-ent-demand',
                'starts_at' => now()->subDays(45),
                'ends_at' => now()->addDays(45),
            ],
            [
                'name' => 'HRMS Launch Webinar Series',
                'slug' => 'hrms-launch-webinar-series',
                'status' => MarketingCampaign::STATUS_ACTIVE,
                'description' => 'Webinar + nurture sequence promoting HRMS workspace.',
                'budget_amount' => 220000,
                'channels' => ['email', 'webinar', 'organic'],
                'audience' => ['persona' => 'HR Director', 'size' => '100-1000'],
                'utm_campaign' => 'hrms-webinar-q3',
                'starts_at' => now()->subDays(20),
                'ends_at' => now()->addDays(40),
            ],
            [
                'name' => 'Partner Co-Marketing — Systems Integrators',
                'slug' => 'partner-co-marketing-si',
                'status' => MarketingCampaign::STATUS_PAUSED,
                'description' => 'Co-branded content with implementation partners.',
                'budget_amount' => 150000,
                'channels' => ['partner', 'content'],
                'audience' => ['persona' => 'Partner AE', 'size' => 'n/a'],
                'utm_campaign' => 'partner-si-2026',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subDays(5),
            ],
            [
                'name' => 'Brand Always-On Search',
                'slug' => 'brand-always-on-search',
                'status' => MarketingCampaign::STATUS_COMPLETED,
                'description' => 'Brand keyword protection and competitor conquesting.',
                'budget_amount' => 320000,
                'channels' => ['google_ads'],
                'audience' => ['persona' => 'In-market searchers'],
                'utm_campaign' => 'brand-search-h1',
                'starts_at' => now()->subMonths(6),
                'ends_at' => now()->subMonths(1),
            ],
        ];

        foreach ($campaigns as $data) {
            MarketingCampaign::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'slug' => $data['slug'],
                ],
                [
                    'created_by' => $owner->id,
                    'name' => $data['name'],
                    'status' => $data['status'],
                    'description' => $data['description'],
                    'budget_amount' => $data['budget_amount'],
                    'budget_currency' => 'INR',
                    'channels' => $data['channels'],
                    'audience' => $data['audience'],
                    'utm_campaign' => $data['utm_campaign'],
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                ],
            );
        }

        if (! Schema::hasTable('marketing_providers')) {
            return;
        }

        foreach ([
            [
                'slug' => 'google_ads',
                'display_name' => 'Google Ads (Demo)',
                'status' => MarketingProvider::STATUS_CONNECTED,
                'external_account_id' => 'demo-gads-1001',
                'capabilities' => ['leads', 'conversions', 'spend'],
            ],
            [
                'slug' => 'meta_ads',
                'display_name' => 'Meta Ads (Demo)',
                'status' => MarketingProvider::STATUS_CONNECTED,
                'external_account_id' => 'demo-meta-2002',
                'capabilities' => ['leads', 'conversions'],
            ],
            [
                'slug' => 'linkedin_ads',
                'display_name' => 'LinkedIn Ads (Demo)',
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'external_account_id' => null,
                'capabilities' => ['leads'],
            ],
        ] as $provider) {
            MarketingProvider::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'slug' => $provider['slug'],
                ],
                [
                    'display_name' => $provider['display_name'],
                    'status' => $provider['status'],
                    'external_account_id' => $provider['external_account_id'],
                    'capabilities' => $provider['capabilities'],
                    'metadata' => ['demo' => true, 'seeded_by' => 'PresentationDemoSeeder'],
                    'last_synced_at' => $provider['status'] === MarketingProvider::STATUS_CONNECTED ? now()->subHours(6) : null,
                    'last_health_at' => $provider['status'] === MarketingProvider::STATUS_CONNECTED ? now()->subHour() : null,
                    'connected_at' => $provider['status'] === MarketingProvider::STATUS_CONNECTED ? now()->subDays(30) : null,
                    'disconnected_at' => $provider['status'] === MarketingProvider::STATUS_DISCONNECTED ? now()->subDays(3) : null,
                ],
            );
        }
    }

    private function ensureDashboardForOrg(Organization $organization): void
    {
        if (class_exists(DashboardProvisioningService::class)) {
            app(DashboardProvisioningService::class)->provision($organization);
        }
    }
}
