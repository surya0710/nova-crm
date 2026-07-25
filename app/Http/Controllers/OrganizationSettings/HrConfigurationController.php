<?php

namespace App\Http\Controllers\OrganizationSettings;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrConfigurationController extends Controller
{
    public function editWorkingDays(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);
        $settings = $organization->settings ?? [];

        return view('organization-settings.working-days', [
            'organization' => $organization,
            'workingDays' => $settings['working_days'] ?? config('hrms.working_days'),
            'weekendDays' => $settings['weekend_days'] ?? config('hrms.weekend_days'),
            'allDays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        ]);
    }

    public function updateWorkingDays(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);
        $validated = $request->validate([
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        ]);

        $workingDays = array_values(array_unique($validated['working_days']));
        $all = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $weekendDays = array_values(array_diff($all, $workingDays));

        $settings = $organization->settings ?? [];
        $settings['working_days'] = $workingDays;
        $settings['weekend_days'] = $weekendDays;
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.working-days.edit')
            ->with('status', 'working-days-updated');
    }

    public function editAttendanceRules(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);
        $settings = $organization->settings['attendance_rules'] ?? [];

        return view('organization-settings.attendance-rules', [
            'organization' => $organization,
            'rules' => [
                'default_grace_minutes' => $settings['default_grace_minutes'] ?? 10,
                'late_threshold_minutes' => $settings['late_threshold_minutes'] ?? 15,
                'overtime_requires_approval' => $settings['overtime_requires_approval'] ?? true,
                'allow_early_clock_in_minutes' => $settings['allow_early_clock_in_minutes'] ?? 30,
            ],
        ]);
    }

    public function updateAttendanceRules(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);
        $validated = $request->validate([
            'default_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'late_threshold_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'allow_early_clock_in_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'overtime_requires_approval' => ['sometimes', 'boolean'],
        ]);

        $settings = $organization->settings ?? [];
        $settings['attendance_rules'] = [
            'default_grace_minutes' => (int) $validated['default_grace_minutes'],
            'late_threshold_minutes' => (int) $validated['late_threshold_minutes'],
            'allow_early_clock_in_minutes' => (int) $validated['allow_early_clock_in_minutes'],
            'overtime_requires_approval' => $request->boolean('overtime_requires_approval'),
        ];
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.attendance-rules.edit')
            ->with('status', 'attendance-rules-updated');
    }

    public function editLeavePolicies(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);
        $settings = $organization->settings['leave_policies'] ?? [];

        return view('organization-settings.leave-policies', [
            'organization' => $organization,
            'policies' => [
                'require_manager_approval' => $settings['require_manager_approval'] ?? true,
                'require_hr_approval' => $settings['require_hr_approval'] ?? false,
                'allow_negative_balance' => $settings['allow_negative_balance'] ?? false,
                'cancellation_cutoff_days' => $settings['cancellation_cutoff_days'] ?? config('hrms.leave_cancellation_cutoff_days', 0),
            ],
        ]);
    }

    public function updateLeavePolicies(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);
        $validated = $request->validate([
            'cancellation_cutoff_days' => ['required', 'integer', 'min:0', 'max:90'],
            'require_manager_approval' => ['sometimes', 'boolean'],
            'require_hr_approval' => ['sometimes', 'boolean'],
            'allow_negative_balance' => ['sometimes', 'boolean'],
        ]);

        $settings = $organization->settings ?? [];
        $settings['leave_policies'] = [
            'require_manager_approval' => $request->boolean('require_manager_approval'),
            'require_hr_approval' => $request->boolean('require_hr_approval'),
            'allow_negative_balance' => $request->boolean('allow_negative_balance'),
            'cancellation_cutoff_days' => (int) $validated['cancellation_cutoff_days'],
        ];
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.leave-policies.edit')
            ->with('status', 'leave-policies-updated');
    }

    public function editLeaveApprovers(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);
        $settings = $organization->settings['leave_approvers'] ?? [];

        return view('organization-settings.leave-approvers', [
            'organization' => $organization,
            'approvers' => [
                'primary_chain' => $settings['primary_chain'] ?? 'reporting_manager',
                'fallback_to_hr' => $settings['fallback_to_hr'] ?? true,
                'notes' => $settings['notes'] ?? '',
            ],
        ]);
    }

    public function updateLeaveApprovers(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);
        $validated = $request->validate([
            'primary_chain' => ['required', 'in:reporting_manager,department_head,hr'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'fallback_to_hr' => ['sometimes', 'boolean'],
        ]);

        $settings = $organization->settings ?? [];
        $settings['leave_approvers'] = [
            'primary_chain' => $validated['primary_chain'],
            'fallback_to_hr' => $request->boolean('fallback_to_hr'),
            'notes' => $validated['notes'] ?? '',
        ];
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.leave-approvers.edit')
            ->with('status', 'leave-approvers-updated');
    }

    public function editNotifications(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);
        $settings = $organization->settings['notifications'] ?? [];

        return view('organization-settings.notifications', [
            'organization' => $organization,
            'notifications' => [
                'employee_welcome' => $settings['employee_welcome'] ?? true,
                'leave_updates' => $settings['leave_updates'] ?? true,
                'interview_invites' => $settings['interview_invites'] ?? true,
                'email_notifications' => $settings['email_notifications'] ?? true,
                'in_app_notifications' => $settings['in_app_notifications'] ?? true,
                'reminder_rules' => $settings['reminder_rules'] ?? '',
                'escalation_rules' => $settings['escalation_rules'] ?? '',
                'digest_preferences' => $settings['digest_preferences'] ?? 'daily',
            ],
        ]);
    }

    public function updateNotifications(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);
        $validated = $request->validate([
            'reminder_rules' => ['nullable', 'string', 'max:2000'],
            'escalation_rules' => ['nullable', 'string', 'max:2000'],
            'digest_preferences' => ['nullable', 'in:off,daily,weekly'],
            'employee_welcome' => ['sometimes', 'boolean'],
            'leave_updates' => ['sometimes', 'boolean'],
            'interview_invites' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'in_app_notifications' => ['sometimes', 'boolean'],
        ]);

        $settings = $organization->settings ?? [];
        $settings['notifications'] = [
            'employee_welcome' => $request->boolean('employee_welcome'),
            'leave_updates' => $request->boolean('leave_updates'),
            'interview_invites' => $request->boolean('interview_invites'),
            'email_notifications' => $request->boolean('email_notifications'),
            'in_app_notifications' => $request->boolean('in_app_notifications'),
            'reminder_rules' => $validated['reminder_rules'] ?? '',
            'escalation_rules' => $validated['escalation_rules'] ?? '',
            'digest_preferences' => $validated['digest_preferences'] ?? 'daily',
        ];
        $organization->update(['settings' => $settings]);

        return redirect()
            ->route('organization.settings.notifications.edit')
            ->with('status', 'notifications-updated');
    }

    public function subscription(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);

        return view('organization-settings.subscription', [
            'organization' => $organization,
        ]);
    }

    public function billing(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);

        return view('organization-settings.billing', [
            'organization' => $organization,
        ]);
    }

    protected function organization(TenantContext $tenant)
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->authorize('viewSettings', $organization);

        return $organization;
    }
}
