<x-platform-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Organization Onboarding')"
        :subtitle="__('Guided setup that reuses provisioning, licensing, identity, import, and branding')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Onboarding'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <form method="post" action="{{ route('platform.onboarding.store') }}">
                @csrf
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Start wizard') }}</x-ui.button>
            </form>
        </x-slot:actions>

        <div class="mb-6 grid gap-3 sm:grid-cols-4">
            <x-ui.stat-card :label="__('Pending setup')" :value="(string) $summary['pending_setup']" />
            <x-ui.stat-card :label="__('In progress')" :value="(string) $summary['in_progress']" />
            <x-ui.stat-card :label="__('Ready')" :value="(string) $summary['ready']" />
            <x-ui.stat-card :label="__('Failed')" :value="(string) $summary['failed']" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-surface-muted/40 text-left text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-3 py-2">{{ __('Organization') }}</th>
                        <th class="px-3 py-2">{{ __('Status') }}</th>
                        <th class="px-3 py-2">{{ __('Step') }}</th>
                        <th class="px-3 py-2">{{ __('Progress') }}</th>
                        <th class="px-3 py-2">{{ __('By') }}</th>
                        <th class="px-3 py-2">{{ __('Updated') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-3 py-2">
                                <a href="{{ route('platform.onboarding.show', $session) }}" class="text-primary-700 hover:underline">
                                    {{ $session->organization?->name ?? __('Draft #:id', ['id' => $session->id]) }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ ucfirst(str_replace('_', ' ', $session->status)) }}</td>
                            <td class="px-3 py-2">{{ $session->current_step }}</td>
                            <td class="px-3 py-2">{{ $session->progress_percent }}%</td>
                            <td class="px-3 py-2 text-ink-muted">{{ $session->initiator?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-ink-muted">{{ $session->updated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-ink-muted">{{ __('No onboarding sessions yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $sessions->links() }}</div>
    </x-layouts.entity-listing>
</x-platform-layout>
