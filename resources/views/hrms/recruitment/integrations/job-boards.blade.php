<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Job Boards')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Job Boards'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $listings = $listings ?? collect();
        $providers = $providers ?? collect();
        $openings = $openings ?? collect();
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Publish Opening') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.job-boards.publish') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="job_opening_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Job Opening') }}</option>
                @foreach ($openings as $opening)
                    <option value="{{ $opening->id }}">{{ $opening->title }} (#{{ $opening->id }})</option>
                @endforeach
            </select>
            <select name="recruitment_provider_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Job Board Provider') }}</option>
                @foreach ($providers as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->display_name ?? $provider->slug }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Publish') }}</x-ui.button>
        </form>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Opening') }}</th>
                    <th class="p-3 text-left">{{ __('Provider') }}</th>
                    <th class="p-3 text-left">{{ __('External ID') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Last Synced') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($listings as $listing)
                <tr class="border-t">
                    <td class="p-3">{{ $listing->jobOpening?->title ?? '#'.$listing->job_opening_id }}</td>
                    <td class="p-3">{{ $listing->provider?->display_name ?? $listing->provider?->slug ?? $listing->recruitment_provider_id }}</td>
                    <td class="p-3">{{ $listing->external_job_id ?? '—' }}</td>
                    <td class="p-3">{{ $listing->status }}</td>
                    <td class="p-3">{{ $listing->last_synced_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $listing->last_error ?? '—' }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('hrms.recruitment.job-boards.sync', $listing) }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Sync') }}</button>
                            </form>
                            @if ($listing->status !== 'closed')
                                <form method="POST" action="{{ route('hrms.recruitment.job-boards.close', $listing) }}">
                                    @csrf
                                    <button type="submit" class="text-rose-600">{{ __('Close') }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No listings yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($listings, 'links'))
            <div class="p-4">{{ $listings->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
