@php
    $emailNotifications = old('email_notifications', $notifications['email_notifications'] ?? true);
    $inAppNotifications = old('in_app_notifications', $notifications['in_app_notifications'] ?? true);
    $reminderRules = old('reminder_rules', $notifications['reminder_rules'] ?? '');
    $escalationRules = old('escalation_rules', $notifications['escalation_rules'] ?? '');
    $digestPreferences = old('digest_preferences', $notifications['digest_preferences'] ?? 'daily');
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Notifications')"
        :subtitle="__('Email, in-app, reminders, and digests')"
    >
        <x-slot:breadcrumbs>
            <x-nav.configuration-breadcrumbs :current="__('Notifications')" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('organization.settings.notifications.update') }}" class="space-y-6 max-w-2xl">
            @csrf
            @method('PUT')

            <x-entity.section :title="__('Channels')">
                <div class="space-y-3">
                    <x-forms.checkbox name="email_notifications" value="1" :label="__('Email notifications')" @checked($emailNotifications) />
                    <x-forms.checkbox name="in_app_notifications" value="1" :label="__('In-app notifications')" @checked($inAppNotifications) />
                </div>
            </x-entity.section>

            <x-entity.section :title="__('HR event alerts')">
                <div class="space-y-3">
                    <x-forms.checkbox name="employee_welcome" value="1" :label="__('Employee welcome notifications')" @checked(old('employee_welcome', $notifications['employee_welcome'] ?? true)) />
                    <x-forms.checkbox name="leave_updates" value="1" :label="__('Leave status updates')" @checked(old('leave_updates', $notifications['leave_updates'] ?? true)) />
                    <x-forms.checkbox name="interview_invites" value="1" :label="__('Interview invitation notifications')" @checked(old('interview_invites', $notifications['interview_invites'] ?? true)) />
                </div>
            </x-entity.section>

            <x-entity.section :title="__('Rules & digests')">
                <div class="grid gap-4">
                    <x-forms.field :label="__('Reminder rules')" name="reminder_rules" :hint="__('Optional notes for follow-up reminders')">
                        <x-forms.textarea name="reminder_rules" rows="3">{{ $reminderRules }}</x-forms.textarea>
                    </x-forms.field>
                    <x-forms.field :label="__('Escalation rules')" name="escalation_rules" :hint="__('Optional notes for escalation paths')">
                        <x-forms.textarea name="escalation_rules" rows="3">{{ $escalationRules }}</x-forms.textarea>
                    </x-forms.field>
                    <x-forms.field :label="__('Digest preferences')" name="digest_preferences">
                        <x-forms.select name="digest_preferences">
                            <option value="off" @selected($digestPreferences === 'off')>{{ __('Off') }}</option>
                            <option value="daily" @selected($digestPreferences === 'daily')>{{ __('Daily') }}</option>
                            <option value="weekly" @selected($digestPreferences === 'weekly')>{{ __('Weekly') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-entity.section>

            <x-ui.button type="submit" variant="primary">{{ __('Save Notification Preferences') }}</x-ui.button>
        </form>
    </x-layouts.settings>
</x-app-layout>
