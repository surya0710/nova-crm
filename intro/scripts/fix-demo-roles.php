<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

$org = Organization::query()->where('name', 'Nova Enterprises')->firstOrFail();
app(TenantContext::class)->set($org);

echo "Available roles:\n";
foreach ($org->roles()->orderBy('slug')->get(['slug', 'name']) as $r) {
    echo " - {$r->slug}\n";
}

function setMembershipRole(Organization $org, User $user, string $roleSlug): void
{
    $role = $org->roles()->where('slug', $roleSlug)->first();
    if (! $role) {
        echo "Missing role {$roleSlug}\n";

        return;
    }

    $exists = $org->users()->where('users.id', $user->id)->exists();
    if (! $exists) {
        $org->addMember($user, $roleSlug);
        echo "Added {$user->email} as {$roleSlug}\n";

        return;
    }

    DB::table('organization_user')
        ->where('organization_id', $org->id)
        ->where('user_id', $user->id)
        ->update([
            'role_id' => $role->id,
            'role' => $roleSlug,
            'is_owner' => $roleSlug === 'organization-owner',
            'is_active' => true,
            'updated_at' => now(),
        ]);
    echo "Updated {$user->email} -> {$roleSlug}\n";
}

$manager = User::query()->where('email', 'priya.sharma@novacrm.test')->firstOrFail();
$hr = User::query()->where('email', 'neha.gupta@novacrm.test')->firstOrFail();
$employee = User::query()->where('email', 'arjun.kapoor@novacrm.test')->firstOrFail();

setMembershipRole($org, $manager, 'manager');
setMembershipRole($org, $hr, 'hr');
setMembershipRole($org, $employee, 'employee');

foreach ([
    'priya.sharma@novacrm.test' => ['hrms.view', 'leave.approve', 'wfh.approve', 'manager.dashboard', 'attendance.view'],
    'neha.gupta@novacrm.test' => ['organization.hr_config.manage', 'attendance.manage', 'wfh.manage', 'leave.manage'],
    'arjun.kapoor@novacrm.test' => ['ess.access'],
] as $email => $perms) {
    $user = User::query()->where('email', $email)->first();
    echo "{$email}:\n";
    foreach ($perms as $perm) {
        echo '  '.$perm.' => '.($user->hasPermission($perm, $org) ? 'yes' : 'NO')."\n";
    }
}
