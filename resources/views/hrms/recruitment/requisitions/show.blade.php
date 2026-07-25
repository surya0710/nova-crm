<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Requisition Details')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Job Requisitions'), 'href' => route('hrms.recruitment.requisitions.index')],
                ['label' => __('Requisition Details'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Department') }}</dt><dd>{{ $requisition->department?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Designation') }}</dt><dd>{{ $requisition->designation?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Employment Type') }}</dt><dd>{{ config('hrms.employment_types.'.$requisition->employment_type, $requisition->employment_type) }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $requisition->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Positions') }}</dt><dd>{{ $requisition->number_of_positions }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Target Joining') }}</dt><dd>{{ $requisition->target_joining_date?->format('Y-m-d') ?? '—' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">{{ __('Business Justification') }}</dt><dd>{{ $requisition->business_justification }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
            @if ($requisition->status === 'draft')
                @can('submit', $requisition)
                <form method="POST" action="{{ route('hrms.recruitment.requisitions.submit', $requisition) }}">@csrf<button class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">{{ __('Submit for Approval') }}</button></form>
                @endcan
            @endif
            @if ($requisition->status === 'pending_approval')
                @can('approve', $requisition)
                <form method="POST" action="{{ route('hrms.recruitment.requisitions.approve', $requisition) }}">@csrf<button class="px-3 py-1 bg-green-600 text-white rounded">{{ __('Approve') }}</button></form>
                <form method="POST" action="{{ route('hrms.recruitment.requisitions.reject', $requisition) }}">@csrf<button class="px-3 py-1 bg-red-600 text-white rounded">{{ __('Reject') }}</button></form>
                @endcan
            @endif
        </div>
    </div>
    @if ($requisition->openings->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-3">{{ __('Related Openings') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($requisition->openings as $opening)
                <li><a href="{{ route('hrms.recruitment.openings.show', $opening) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $opening->title }}</a> — {{ $opening->statusLabel() }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
