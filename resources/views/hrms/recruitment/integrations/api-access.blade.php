<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Recruitment API Access')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment API Access'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $endpoints = $endpoints ?? [];
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6 text-sm text-slate-700 space-y-2">
        <h2 class="font-medium text-slate-900">{{ __('Authentication') }}</h2>
        <p>{{ __('Use a Sanctum Bearer token in the Authorization header.') }}</p>
        <p>{{ __('Include the X-Organization-Id header with your organization ID.') }}</p>
        <p>{{ __('The organization must have API access enabled, and the token owner needs recruitment.* permissions for the requested resources.') }}</p>
        <pre class="mt-3 rounded-md bg-slate-50 border border-slate-200 p-3 text-xs overflow-x-auto">Authorization: Bearer {token}
X-Organization-Id: {organization_id}</pre>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Method') }}</th>
                    <th class="p-3 text-left">{{ __('Path') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($endpoints as $endpoint)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $endpoint['method'] ?? '—' }}</td>
                    <td class="p-3 font-mono text-xs">{{ $endpoint['path'] ?? '—' }}</td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="2">{{ __('No endpoints documented.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </x-layouts.settings>
</x-app-layout>
