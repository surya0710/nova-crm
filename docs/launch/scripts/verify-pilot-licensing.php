<?php

use App\Models\Employee;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\Project;
use App\Services\Dashboard\ModuleSubscriptionService;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slugs = [
    'apex-sales-partners',
    'meridian-people-works',
    'cascade-growth-labs',
    'harbor-delivery-collective',
    'summit-enterprise-group',
];

$svc = app(ModuleSubscriptionService::class);

foreach ($slugs as $slug) {
    $o = Organization::query()->where('slug', $slug)->first();
    if (! $o) {
        echo "{$slug} MISSING\n";
        continue;
    }

    $enabled = OrganizationModule::query()
        ->where('organization_id', $o->id)
        ->where('is_enabled', true)
        ->orderBy('module_key')
        ->pluck('module_key')
        ->all();

    echo $o->name
        .' | plan='.$o->plan
        .' | users='.$o->users()->count()
        .' | employees='.Employee::query()->where('organization_id', $o->id)->count()
        .' | leads='.Lead::query()->where('organization_id', $o->id)->count()
        .' | projects='.Project::query()->where('organization_id', $o->id)->count()
        ."\n";
    echo '  enabled='.implode(',', $enabled)."\n";
    echo '  crm='.($svc->moduleAllowed($o, 'crm') ? 'Y' : 'N')
        .' projects='.($svc->moduleAllowed($o, 'projects') ? 'Y' : 'N')
        .' hrms='.($svc->moduleAllowed($o, 'hrms') ? 'Y' : 'N')
        .' marketing='.($svc->moduleAllowed($o, 'marketing') ? 'Y' : 'N')
        .' analytics='.($svc->moduleAllowed($o, 'analytics') ? 'Y' : 'N')
        ."\n\n";
}
