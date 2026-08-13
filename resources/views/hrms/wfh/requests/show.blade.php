<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('WFH Request')"
        :subtitle="$wfhRequest->dateLabel()"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('WFH Requests'), 'href' => route('hrms.wfh.requests.index')],
                ['label' => $wfhRequest->dateLabel(), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ __('Employee') }}</dt>
                        <dd class="text-sm font-medium text-ink-heading">{{ $wfhRequest->employee?->full_name ?? trim(($wfhRequest->employee?->first_name ?? '').' '.($wfhRequest->employee?->last_name ?? '')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ __('Status') }}</dt>
                        <dd class="text-sm font-medium text-ink-heading">{{ __($statuses[$wfhRequest->status] ?? $wfhRequest->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ __('Dates') }}</dt>
                        <dd class="text-sm text-ink-heading">{{ $wfhRequest->dateLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-ink-muted">{{ __('Submitted') }}</dt>
                        <dd class="text-sm text-ink-heading">{{ $wfhRequest->submitted_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase text-ink-muted">{{ __('Reason') }}</dt>
                        <dd class="text-sm text-ink-heading">{{ $wfhRequest->reason ?: '—' }}</dd>
                    </div>
                    @if (($orgPolicy['bypass_geofence'] ?? false) && $wfhRequest->status === 'approved')
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase text-ink-muted">{{ __('Attendance') }}</dt>
                            <dd class="text-sm text-emerald-700">{{ __('Geofence exemption applies on these WFH dates (when organization policy allows).') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>

            <x-ui.card>
                <h3 class="mb-3 text-sm font-semibold text-ink-heading">{{ __('Actions') }}</h3>
                <div class="space-y-3">
                    @can('approve', $wfhRequest)
                        <form method="POST" action="{{ route('hrms.wfh.requests.approve', $wfhRequest) }}" class="space-y-2">
                            @csrf
                            <textarea name="remarks" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Remarks (optional)') }}"></textarea>
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('hrms.wfh.requests.reject', $wfhRequest) }}" class="space-y-2">
                            @csrf
                            <textarea name="remarks" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Rejection remarks') }}"></textarea>
                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Reject') }}</x-ui.button>
                        </form>
                    @endcan
                    @can('cancel', $wfhRequest)
                        <form method="POST" action="{{ route('hrms.wfh.requests.cancel', $wfhRequest) }}" class="space-y-2" onsubmit="return confirm(@json(__('Cancel this approved WFH request?')))">
                            @csrf
                            <textarea name="remarks" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Cancellation remarks') }}"></textarea>
                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Cancel approved WFH') }}</x-ui.button>
                        </form>
                    @endcan
                </div>
            </x-ui.card>
        </div>

        <x-ui.card class="mt-6">
            <h3 class="mb-3 text-sm font-semibold text-ink-heading">{{ __('Approval history') }}</h3>
            <div class="space-y-2">
                @forelse ($wfhRequest->approvalSteps as $step)
                    <div class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <div>
                            <div class="font-medium">{{ __('Step') }} {{ $step->step_order }}</div>
                            <div class="text-ink-muted">
                                {{ $step->approverEmployee?->full_name
                                    ?? $step->approverUser?->name
                                    ?? __('HR / any WFH manager') }}
                            </div>
                            @if ($step->comments)
                                <div class="mt-1 text-ink-muted">{{ $step->comments }}</div>
                            @endif
                        </div>
                        <div class="text-right text-ink-muted">
                            <div>{{ __($stepStatuses[$step->status] ?? $step->status) }}</div>
                            @if ($step->acted_at)
                                <div class="text-xs">{{ $step->acted_at->format('M j, Y g:i A') }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('No approval steps.') }}</p>
                @endforelse
            </div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
