<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Recruitment Webhooks')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Webhooks'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $endpoints = $endpoints ?? collect();
        $deliveries = $deliveries ?? collect();
        $events = $events ?? [];
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Create Endpoint') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.webhooks.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
                <x-forms.input name="url" type="url" placeholder="{{ __('URL') }}" required  />
                <x-forms.input name="secret" placeholder="{{ __('Secret (optional)') }}"  />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-700 mb-2">{{ __('Events') }}</p>
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach ($events as $eventKey => $eventLabel)
                        <label class="inline-flex items-center gap-1.5">
                            <input type="checkbox" name="events[]" value="{{ $eventKey }}" class="rounded border-slate-300">
                            <span>{{ $eventKey }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Endpoint') }}</x-ui.button>
        </form>
    </div>

    @if ($endpoints->isNotEmpty())
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden mb-6">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Name') }}</th>
                        <th class="p-3 text-left">{{ __('URL') }}</th>
                        <th class="p-3 text-left">{{ __('Events') }}</th>
                        <th class="p-3 text-left">{{ __('Active') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($endpoints as $endpoint)
                    <tr class="border-t">
                        <td class="p-3">{{ $endpoint->name }}</td>
                        <td class="p-3 font-mono text-xs truncate max-w-xs">{{ $endpoint->url }}</td>
                        <td class="p-3">{{ implode(', ', $endpoint->events ?? []) }}</td>
                        <td class="p-3">{{ $endpoint->is_active ? __('Yes') : __('No') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Event') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Attempts') }}</th>
                    <th class="p-3 text-left">{{ __('HTTP') }}</th>
                    <th class="p-3 text-left">{{ __('Delivered') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($deliveries as $delivery)
                <tr class="border-t">
                    <td class="p-3">{{ $delivery->event_key }}</td>
                    <td class="p-3">{{ $delivery->status }}</td>
                    <td class="p-3">{{ $delivery->attempt_count }}</td>
                    <td class="p-3">{{ $delivery->http_status ?? '—' }}</td>
                    <td class="p-3">{{ $delivery->delivered_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $delivery->last_error ?? '—' }}</td>
                    <td class="p-3">
                        <form method="POST" action="{{ route('hrms.recruitment.webhooks.retry', $delivery) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Retry') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No deliveries yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($deliveries, 'links'))
            <div class="p-4">{{ $deliveries->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
