<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$opening->title"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Job Openings'), 'href' => route('hrms.recruitment.openings.index')],
                ['label' => $opening->title, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $opening->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Location') }}</dt><dd>{{ $opening->location ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Department') }}</dt><dd>{{ $opening->department?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Designation') }}</dt><dd>{{ $opening->designation?->name }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">{{ __('Description') }}</dt><dd>{{ $opening->description ?? '—' }}</dd></div>
        </dl>
        @if ($opening->status === 'draft')
            @can('publish', $opening)
            <form method="POST" action="{{ route('hrms.recruitment.openings.publish', $opening) }}" class="mt-4">@csrf<button class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">{{ __('Publish Opening') }}</button></form>
            @endcan
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
