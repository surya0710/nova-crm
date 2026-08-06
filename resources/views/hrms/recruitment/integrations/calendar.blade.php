<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Calendar Sync')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Calendar Sync'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $providers = $providers ?? collect();
        $events = $events ?? collect();
        $rounds = $rounds ?? collect();
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Sync Interview Event') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.calendar.sync') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            @if ($rounds->isNotEmpty())
                <select name="interview_round_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <option value="">{{ __('Interview Round') }}</option>
                    @foreach ($rounds as $round)
                        <option value="{{ $round->id }}">
                            #{{ $round->id }} — {{ $round->jobApplication?->candidate?->fullName() ?? __('Round') }}
                            @if ($round->scheduled_at) ({{ $round->scheduled_at->format('Y-m-d H:i') }}) @endif
                        </option>
                    @endforeach
                </select>
            @else
                <x-forms.input name="interview_round_id" type="number" placeholder="{{ __('Interview Round ID') }}" required  />
            @endif
            <select name="recruitment_provider_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Calendar Provider') }}</option>
                @foreach ($providers as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->display_name ?? $provider->slug }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Sync Event') }}</x-ui.button>
        </form>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('ID') }}</th>
                    <th class="p-3 text-left">{{ __('Round') }}</th>
                    <th class="p-3 text-left">{{ __('Provider') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Meeting Link') }}</th>
                    <th class="p-3 text-left">{{ __('Synced At') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($events as $event)
                <tr class="border-t">
                    <td class="p-3">{{ $event->id }}</td>
                    <td class="p-3">{{ $event->interview_round_id }}</td>
                    <td class="p-3">{{ $event->provider?->display_name ?? $event->provider?->slug ?? $event->recruitment_provider_id }}</td>
                    <td class="p-3">{{ $event->status }}</td>
                    <td class="p-3">
                        @if ($event->meeting_link)
                            <a href="{{ $event->meeting_link }}" class="text-sm font-medium text-primary-700 hover:text-primary-800" target="_blank" rel="noopener">{{ __('Open') }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-3">{{ $event->synced_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $event->last_error ?? '—' }}</td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No calendar events yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($events, 'links'))
            <div class="p-4">{{ $events->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
