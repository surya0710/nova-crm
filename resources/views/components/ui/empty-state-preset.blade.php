@props([
    'variant' => 'generic',
    'title' => null,
    'description' => null,
    'actionHref' => null,
    'actionLabel' => null,
])
@php
$presets = [
    'leads' => [
        'title' => __('No leads yet'),
        'description' => __('Get started by adding your first lead.'),
        'actionLabel' => __('Add Lead'),
    ],
    'search' => [
        'title' => __('No search results'),
        'description' => __('Try a different name, company, or email.'),
    ],
    'activities' => [
        'title' => __('No activities'),
        'description' => __('Notes, follow-ups, and assignments will show up here.'),
    ],
    'attachments' => [
        'title' => __('No attachments'),
        'description' => __('Upload files related to this record.'),
    ],
    'timeline' => [
        'title' => __('No timeline events'),
        'description' => __('Activity and system events will appear here.'),
    ],
    'saved_views' => [
        'title' => __('No saved views'),
        'description' => __('Save filters from a listing to reuse them later.'),
    ],
    'projects' => [
        'title' => __('No projects yet'),
        'description' => __('Get started by creating your first project.'),
        'actionLabel' => __('Add Project'),
    ],
    'tasks' => [
        'title' => __('No tasks yet'),
        'description' => __('Create a task to track delivery work.'),
        'actionLabel' => __('Add Task'),
    ],
    'portfolios' => [
        'title' => __('No portfolios yet'),
        'description' => __('Group related programs and projects into a portfolio.'),
        'actionLabel' => __('Add Portfolio'),
    ],
    'programs' => [
        'title' => __('No programs yet'),
        'description' => __('Create a program to coordinate related projects.'),
        'actionLabel' => __('Add Program'),
    ],
    'risks' => [
        'title' => __('No risks yet'),
        'description' => __('Log risks to track threats to delivery.'),
        'actionLabel' => __('Add Risk'),
    ],
    'issues' => [
        'title' => __('No issues yet'),
        'description' => __('Track blockers and defects affecting projects.'),
        'actionLabel' => __('Add Issue'),
    ],
    'reports' => [
        'title' => __('No reports available'),
        'description' => __('Reports will appear here when you have access.'),
    ],
    'resources' => [
        'title' => __('No resources yet'),
        'description' => __('Allocate people and capacity to projects.'),
    ],
    'milestones' => [
        'title' => __('No milestones yet'),
        'description' => __('Define checkpoints and key deliverables.'),
    ],
    'employees' => [
        'title' => __('No employees yet'),
        'description' => __('Add your first employee to start building the directory.'),
        'actionLabel' => __('Add Employee'),
    ],
    'attendance' => [
        'title' => __('No attendance records'),
        'description' => __('Attendance for this period will appear here.'),
    ],
    'leave' => [
        'title' => __('No leave requests'),
        'description' => __('Pending and historical leave requests will show up here.'),
        'actionLabel' => __('Apply Leave'),
    ],
    'recruitment' => [
        'title' => __('No recruitment activity'),
        'description' => __('Openings, applications, and pipeline activity will appear here.'),
        'actionLabel' => __('Post Opening'),
    ],
    'candidates' => [
        'title' => __('No candidates yet'),
        'description' => __('Add candidates or publish openings to start the pipeline.'),
        'actionLabel' => __('Add Candidate'),
    ],
    'assets' => [
        'title' => __('No assets yet'),
        'description' => __('Register company assets to track assignments.'),
        'actionLabel' => __('Add Asset'),
    ],
    'payroll' => [
        'title' => __('No payroll runs'),
        'description' => __('Payroll runs and payslips will appear here when available.'),
    ],
    'performance' => [
        'title' => __('No performance records'),
        'description' => __('Goals, reviews, and appraisals will show up here.'),
    ],
    'documents' => [
        'title' => __('No documents yet'),
        'description' => __('Upload employee documents to keep records organized.'),
    ],
    'organizations' => [
        'title' => __('No organizations yet'),
        'description' => __('Create your first tenant organization to get started.'),
        'actionLabel' => __('New Organization'),
    ],
    'subscriptions' => [
        'title' => __('No subscriptions found'),
        'description' => __('Active, trial, and renewal subscriptions will appear here.'),
    ],
    'providers' => [
        'title' => __('No providers configured'),
        'description' => __('Integration providers will appear here once registered.'),
    ],
    'tickets' => [
        'title' => __('No support tickets'),
        'description' => __('Customer support tickets will show up here.'),
        'actionLabel' => __('Create Ticket'),
    ],
    'plans' => [
        'title' => __('No plans available'),
        'description' => __('Subscription plans will appear here when configured.'),
    ],
    'platform_audit' => [
        'title' => __('No audit entries'),
        'description' => __('Platform and tenant activity will be recorded here.'),
    ],
    'users' => [
        'title' => __('No users yet'),
        'description' => __('Invite teammates to collaborate in this organization.'),
        'actionLabel' => __('Invite User'),
    ],
    'roles' => [
        'title' => __('No roles yet'),
        'description' => __('Create roles to control access across the workspace.'),
        'actionLabel' => __('Create Role'),
    ],
    'integrations' => [
        'title' => __('No integrations connected'),
        'description' => __('Connect providers to sync marketing and messaging channels.'),
        'actionLabel' => __('Open Integrations'),
    ],
    'api_tokens' => [
        'title' => __('No API tokens'),
        'description' => __('Create a personal access token to call the REST API.'),
        'actionLabel' => __('Create Token'),
    ],
    'departments' => [
        'title' => __('No departments yet'),
        'description' => __('Define departments for reporting structure and HR workflows.'),
        'actionLabel' => __('Add Department'),
    ],
    'branches' => [
        'title' => __('No branches yet'),
        'description' => __('Add office locations and branches for your organization.'),
        'actionLabel' => __('Add Branch'),
    ],
    'admin_audit' => [
        'title' => __('No audit activity'),
        'description' => __('Organization changes and security events will appear here.'),
    ],
    'settings' => [
        'title' => __('No settings matched'),
        'description' => __('Try a different configuration section name.'),
    ],
    'modules' => [
        'title' => __('No modules listed'),
        'description' => __('Module entitlements for the current plan will show here.'),
    ],
    'security' => [
        'title' => __('No security events'),
        'description' => __('Login, logout, and session events will appear here.'),
    ],
    'campaigns' => [
        'title' => __('No campaigns yet'),
        'description' => __('Create a campaign to track budget, channels, and attribution performance.'),
        'actionLabel' => __('Create Campaign'),
    ],
    'attribution' => [
        'title' => __('No attribution data'),
        'description' => __('Attributed leads and touches will appear as marketing traffic is tracked.'),
    ],
    'analytics' => [
        'title' => __('No analytics yet'),
        'description' => __('Cross-module metrics will appear when you have access to reports and dashboards.'),
    ],
    'dashboards' => [
        'title' => __('No dashboards configured'),
        'description' => __('Choose a template or customize your personal dashboard widgets.'),
        'actionLabel' => __('Open Home Dashboard'),
    ],
    'kpis' => [
        'title' => __('No KPIs matched'),
        'description' => __('Shared KPI definitions will appear here by category.'),
    ],
    'ai_insights' => [
        'title' => __('No insights available'),
        'description' => __('AI-assisted insights will appear when there is enough operational data. Human review is always required.'),
    ],
    'generic' => [
        'title' => __('Nothing here yet'),
        'description' => null,
    ],
];$preset = $presets[$variant] ?? $presets['generic'];
$resolvedTitle = $title ?? $preset['title'];
$resolvedDescription = $description ?? ($preset['description'] ?? null);
$resolvedActionLabel = $actionLabel ?? ($preset['actionLabel'] ?? null);
@endphp
<x-ui.empty-state :title="$resolvedTitle" :description="$resolvedDescription" {{ $attributes }}>
    @isset($icon)
        <x-slot:icon>{{ $icon }}</x-slot:icon>
    @else
        <x-slot:icon>
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </x-slot:icon>
    @endisset
    @if ($actionHref && $resolvedActionLabel)
        <x-slot:actions>
            <x-ui.button :href="$actionHref" variant="primary" size="sm">{{ $resolvedActionLabel }}</x-ui.button>
        </x-slot:actions>
    @elseif (isset($actions))
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
    {{ $slot }}
</x-ui.empty-state>
