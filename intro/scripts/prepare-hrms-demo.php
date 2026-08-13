<?php

/**
 * Prepare Nova Enterprises demo tenant for HRMS product-intro recording.
 * Idempotent. Does not wipe existing data.
 *
 * Usage: php intro/scripts/prepare-hrms-demo.php
 */

declare(strict_types=1);

use App\Models\AttendanceGeofence;
use App\Models\AttendanceRecord;
use App\Models\AttendanceVerificationAudit;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\EmployeeWfhAssignment;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureComponent;
use App\Models\User;
use App\Models\WfhApprovalStep;
use App\Models\WfhRequest;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$password = 'password';

$org = Organization::query()->where('name', 'Nova Enterprises')->first();
if (! $org) {
    fwrite(STDERR, "Nova Enterprises organization not found. Run: php artisan demo:seed-presentation\n");
    exit(1);
}

app(TenantContext::class)->set($org);
echo "Org: {$org->name} (#{$org->id})\n";

function upsertDemoUser(string $email, string $name, string $password): User
{
    return User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ],
    );
}

function ensureMember(Organization $org, User $user, string $roleSlug): void
{
    $role = $org->roles()->where('slug', $roleSlug)->first();
    if (! $role) {
        echo "  ! missing role {$roleSlug} for {$user->email}\n";

        return;
    }

    if (! $org->users()->whereKey($user->id)->exists()) {
        $org->addMember($user, $roleSlug);
        echo "  + member {$user->email} as {$roleSlug}\n";

        return;
    }

    \Illuminate\Support\Facades\DB::table('organization_user')
        ->where('organization_id', $org->id)
        ->where('user_id', $user->id)
        ->update([
            'role_id' => $role->id,
            'role' => $roleSlug,
            'is_owner' => $roleSlug === 'organization-owner',
            'is_active' => true,
            'updated_at' => now(),
        ]);

    echo "  = member {$user->email} as {$roleSlug}\n";
}

$emailFixes = [
    'neha.gupta@@NOVA_EMAIL_DOMAIN@@' => ['neha.gupta@novacrm.test', 'Neha Gupta'],
    'priya.sharma@@NOVA_EMAIL_DOMAIN@@' => ['priya.sharma@novacrm.test', 'Priya Sharma'],
    'amit.verma@@NOVA_EMAIL_DOMAIN@@' => ['kavita.nair@novacrm.test', 'Kavita Nair'],
];

foreach ($emailFixes as $broken => [$fixed, $name]) {
    $user = User::query()->where('email', $broken)->first();
    if (! $user) {
        continue;
    }
    $conflict = User::query()->where('email', $fixed)->where('id', '!=', $user->id)->exists();
    if ($conflict) {
        echo "Skip rename {$broken} -> {$fixed} (target exists)\n";
        continue;
    }
    $user->update([
        'email' => $fixed,
        'name' => $name,
        'password' => Hash::make($password),
    ]);
    echo "Fixed email {$broken} -> {$fixed}\n";
}

$accounts = [
    'admin' => ['demo@novacrm.test', 'Rajesh Mehta', 'organization-owner'],
    'hr' => ['neha.gupta@novacrm.test', 'Neha Gupta', 'hr'],
    'manager' => ['priya.sharma@novacrm.test', 'Priya Sharma', 'manager'],
    'employee' => ['arjun.kapoor@novacrm.test', 'Arjun Kapoor', 'employee'],
    'recruiter' => ['pooja.saxena@novacrm.test', 'Pooja Saxena', 'hr'],
];

$users = [];
foreach ($accounts as $key => [$email, $name, $role]) {
    $user = upsertDemoUser($email, $name, $password);
    ensureMember($org, $user, $role);
    $users[$key] = $user;
}

$linkMap = [
    'demo@novacrm.test' => 'NE-001',
    'neha.gupta@novacrm.test' => 'NE-012',
    'priya.sharma@novacrm.test' => 'NE-002',
    'arjun.kapoor@novacrm.test' => 'NE-003',
    'pooja.saxena@novacrm.test' => 'NE-013',
];

foreach ($linkMap as $email => $code) {
    $user = User::query()->where('email', $email)->first();
    $employee = Employee::query()->where('organization_id', $org->id)->where('employee_code', $code)->first();
    if ($user && $employee && (int) $employee->user_id !== (int) $user->id) {
        $employee->update(['user_id' => $user->id]);
        echo "Linked {$code} -> {$email}\n";
    }
}

$settings = $org->settings ?? [];
$settings['attendance_rules'] = array_merge($settings['attendance_rules'] ?? [], [
    'attendance_verification_mode' => 'geofence',
    'max_accuracy_meters' => 100,
    'require_device_id' => false,
]);
$settings['wfh_policies'] = array_merge([
    'enabled' => true,
    'default_policy_type' => 'daily',
    'requires_approval' => true,
    'requires_hr_approval' => true,
    'bypass_geofence' => true,
    'record_gps_when_wfh' => true,
    'allowed_weekdays' => [1, 2, 3, 4, 5],
    'cancellation_cutoff_days' => 1,
], $settings['wfh_policies'] ?? []);
$org->settings = $settings;
$org->save();
echo "Updated attendance + WFH organization settings\n";

$hq = Branch::query()->where('organization_id', $org->id)->orderBy('id')->first();
$blr = Branch::query()
    ->where('organization_id', $org->id)
    ->when($hq, fn ($q) => $q->where('id', '!=', $hq->id))
    ->orderBy('id')
    ->first();

if ($hq && Schema::hasTable('attendance_geofences')) {
    AttendanceGeofence::query()->updateOrCreate(
        [
            'organization_id' => $org->id,
            'name' => 'Mumbai HQ Campus',
        ],
        [
            'branch_id' => $hq->id,
            'latitude' => 19.0760000,
            'longitude' => 72.8777000,
            'radius_meters' => 200,
            'is_active' => true,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
        ],
    );

    if ($blr) {
        AttendanceGeofence::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'name' => 'Bengaluru Delivery Center',
            ],
            [
                'branch_id' => $blr->id,
                'latitude' => 12.9716000,
                'longitude' => 77.5946000,
                'radius_meters' => 150,
                'is_active' => true,
                'effective_from' => now()->subMonths(3)->toDateString(),
                'effective_to' => null,
            ],
        );
    }
    echo "Geofences ready\n";
}

$arjun = Employee::query()->where('organization_id', $org->id)->where('employee_code', 'NE-003')->first();
$priya = Employee::query()->where('organization_id', $org->id)->where('employee_code', 'NE-002')->first();

if ($priya && Schema::hasTable('employee_wfh_assignments')) {
    EmployeeWfhAssignment::query()->updateOrCreate(
        [
            'organization_id' => $org->id,
            'employee_id' => $priya->id,
            'policy_type' => 'permanent',
        ],
        [
            'weekdays' => null,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'reason' => 'Engineering lead permanent hybrid WFH',
            'assigned_by' => $users['hr']->id,
        ],
    );
    echo "Permanent WFH assignment for Priya ready\n";
}

if ($arjun && Schema::hasTable('wfh_requests')) {
    $start = now()->next(Carbon\Carbon::MONDAY)->startOfDay();
    if ($start->lt(now())) {
        $start = now()->addWeek()->next(Carbon\Carbon::MONDAY)->startOfDay();
    }
    $end = $start->copy()->addDays(2);

    $request = WfhRequest::query()->updateOrCreate(
        [
            'organization_id' => $org->id,
            'employee_id' => $arjun->id,
            'work_date' => $start->toDateString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ],
        [
            'status' => 'pending',
            'reason' => 'Client delivery sprint — remote collaboration days',
            'submitted_at' => now(),
            'cancelled_at' => null,
        ],
    );

    if (Schema::hasTable('wfh_approval_steps')) {
        WfhApprovalStep::query()->updateOrCreate(
            [
                'wfh_request_id' => $request->id,
                'step_order' => 1,
            ],
            [
                'organization_id' => $org->id,
                'approver_employee_id' => $priya?->id,
                'approver_user_id' => $users['manager']->id,
                'status' => 'pending',
                'acted_at' => null,
                'comments' => null,
            ],
        );
        WfhApprovalStep::query()->updateOrCreate(
            [
                'wfh_request_id' => $request->id,
                'step_order' => 2,
            ],
            [
                'organization_id' => $org->id,
                'approver_employee_id' => Employee::query()
                    ->where('organization_id', $org->id)
                    ->where('employee_code', 'NE-012')
                    ->value('id'),
                'approver_user_id' => $users['hr']->id,
                'status' => 'pending',
                'acted_at' => null,
                'comments' => null,
            ],
        );
    }
    echo "Pending multi-day WFH request for Arjun ready\n";
}

if ($arjun && Schema::hasTable('attendance_verification_audits')) {
    $record = AttendanceRecord::query()
        ->where('organization_id', $org->id)
        ->where('employee_id', $arjun->id)
        ->orderByDesc('attendance_date')
        ->first();

    if ($record) {
        AttendanceVerificationAudit::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'attendance_record_id' => $record->id,
                'event' => 'clock_in',
            ],
            [
                'employee_id' => $arjun->id,
                'verification_mode' => 'geofence',
                'verification_status' => 'verified',
                'reason' => null,
                'latitude' => 19.0760500,
                'longitude' => 72.8777200,
                'accuracy_meters' => 18,
                'device_id' => 'demo-device-arjun',
                'geofence_id' => AttendanceGeofence::query()->where('organization_id', $org->id)->value('id'),
                'metadata' => [
                    'demo' => true,
                    'source' => 'intro_prepare',
                    'branch' => 'Mumbai HQ Campus',
                    'distance_meters' => 12,
                ],
                'actor_id' => $users['employee']->id,
                'verified_at' => now()->subDays(1),
            ],
        );
        echo "Sample verification audit attached\n";
    }
}

if (Schema::hasTable('salary_components')) {
    $basic = SalaryComponent::query()->updateOrCreate(
        ['organization_id' => $org->id, 'code' => 'BASIC'],
        [
            'name' => 'Basic Pay',
            'component_type' => 'earning',
            'is_taxable' => true,
            'is_recurring' => true,
            'formula_supported' => false,
            'is_active' => true,
            'description' => 'Demo basic pay component',
        ],
    );
    $hra = SalaryComponent::query()->updateOrCreate(
        ['organization_id' => $org->id, 'code' => 'HRA'],
        [
            'name' => 'House Rent Allowance',
            'component_type' => 'earning',
            'is_taxable' => true,
            'is_recurring' => true,
            'formula_supported' => false,
            'is_active' => true,
            'description' => 'Demo HRA component',
        ],
    );
    $pf = SalaryComponent::query()->updateOrCreate(
        ['organization_id' => $org->id, 'code' => 'PF'],
        [
            'name' => 'Provident Fund',
            'component_type' => 'deduction',
            'is_taxable' => false,
            'is_recurring' => true,
            'formula_supported' => false,
            'is_active' => true,
            'description' => 'Demo PF deduction',
        ],
    );

    if (Schema::hasTable('salary_structures')) {
        $structure = SalaryStructure::query()->updateOrCreate(
            ['organization_id' => $org->id, 'name' => 'Standard Grade A'],
            [
                'description' => 'Demo structure for Konnect Nex HRMS intro',
                'effective_date' => now()->startOfYear()->toDateString(),
                'is_active' => true,
            ],
        );

        if (Schema::hasTable('salary_structure_components')) {
            $amounts = ['BASIC' => 40000, 'HRA' => 16000, 'PF' => 1800];
            $sort = 1;
            foreach ([$basic, $hra, $pf] as $component) {
                SalaryStructureComponent::query()->updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'salary_structure_id' => $structure->id,
                        'salary_component_id' => $component->id,
                    ],
                    [
                        'calculation_type' => 'fixed',
                        'amount' => $amounts[$component->code] ?? 0,
                        'percentage' => null,
                        'based_on_component_id' => null,
                        'formula' => null,
                        'sort_order' => $sort++,
                    ],
                );
            }
        }

        if ($arjun && Schema::hasTable('employee_salary_assignments')) {
            EmployeeSalaryAssignment::query()->updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'employee_id' => $arjun->id,
                    'salary_structure_id' => $structure->id,
                ],
                [
                    'effective_from' => now()->startOfMonth()->toDateString(),
                    'effective_until' => null,
                    'annual_ctc' => 720000,
                    'notes' => 'Demo salary assignment',
                    'assigned_by' => $users['hr']->id,
                ],
            );
        }
    }

    if (Schema::hasTable('payroll_periods')) {
        PayrollPeriod::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'name' => now()->format('F Y').' Payroll',
            ],
            [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'open',
            ],
        );
    }

    echo "Payroll demo configuration ready\n";
}

echo "\nDemo accounts (password: {$password}):\n";
foreach ($accounts as $key => [$email, $name]) {
    echo sprintf("  %-10s %s (%s)\n", $key, $email, $name);
}
echo "Done.\n";
