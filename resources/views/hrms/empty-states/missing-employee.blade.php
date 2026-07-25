<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$title ?? __('Self-Service')"
        :subtitle="__('Employee profile required')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => $title ?? __('Self-Service'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('dashboard')" variant="secondary" size="sm">{{ __('Back to Dashboard') }}</x-ui.button>
        </x-slot:actions>

        <x-ui.card>
            <x-ui.empty-state
                :title="$message"
                :description="match ($audience ?? 'employee') {
                    'manager' => __('Once employees report to you, team insights will appear here.'),
                    'hr' => __('Create employees from HR or Organization Settings to get started.'),
                    'supervisor' => __('Assign team members to see supervision tools.'),
                    default => __('Ask your HR administrator to link an employee profile to your account.'),
                }"
            />
        </x-ui.card>
    </x-layouts.entity-detail>
</x-app-layout>
