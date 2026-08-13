<x-app-layout>
    <x-flash-messages />

    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ __('Work From Home') }}</h1>
            <p class="text-sm text-slate-600">{{ __('Request WFH for a day or date range and view your active assignments.') }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold text-slate-900">{{ __('Today') }}</h2>
            @if ($todayResolution['is_wfh'])
                <p class="text-sm text-emerald-700">
                    {{ __('You are on WFH today (:type).', ['type' => __(config('hrms.wfh_policy_types.'.$todayResolution['policy_type'], $todayResolution['policy_type']))]) }}
                </p>
                @if ($todayResolution['bypass_geofence'] ?? false)
                    <p class="mt-1 text-xs text-emerald-700">{{ __('Office geofence is exempted for attendance today.') }}</p>
                @endif
            @elseif ($todayResolution['suppressed_by_leave'] ?? false)
                <p class="text-sm text-blue-700">{{ __('Approved leave is active today. Leave takes precedence over WFH.') }}</p>
            @else
                <p class="text-sm text-slate-600">{{ __('You are not on WFH today.') }}</p>
            @endif
            @unless($orgPolicy['enabled'])
                <p class="mt-2 text-sm text-amber-700">{{ __('WFH is currently disabled by your organization.') }}</p>
            @endunless
        </div>

        @if ($upcoming->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Upcoming approved WFH') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($upcoming as $item)
                        <li class="flex items-center justify-between rounded-md border border-slate-100 px-3 py-2">
                            <span>{{ $item->dateLabel() }}</span>
                            <span class="text-emerald-700">{{ __($statuses[$item->status] ?? $item->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($orgPolicy['enabled'])
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Request WFH') }}</h2>
                <form method="POST" action="{{ route('ess.wfh.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm text-slate-600">{{ __('Start date') }}</label>
                        <input type="date" name="start_date" value="{{ old('start_date', old('work_date')) }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('work_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-600">{{ __('End date') }}</label>
                        <input type="date" name="end_date" value="{{ old('end_date', old('start_date', old('work_date'))) }}" class="w-full rounded-md border-slate-300 text-sm">
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank for a single day.') }}</p>
                        @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm text-slate-600">{{ __('Reason') }}</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div>
                        <x-primary-button>{{ __('Submit request') }}</x-primary-button>
                    </div>
                </form>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Your assignments') }}</h2>
            @forelse ($assignments as $assignment)
                <div class="mb-2 rounded-md border border-slate-100 px-3 py-2 text-sm">
                    <div class="font-medium">{{ __(config('hrms.wfh_policy_types.'.$assignment->policy_type, $assignment->policy_type)) }}</div>
                    <div class="text-slate-600">
                        {{ $assignment->effective_from?->format('M j, Y') }} → {{ $assignment->effective_to?->format('M j, Y') ?? __('Ongoing') }}
                        · {{ $assignment->is_active ? __('Active') : __('Inactive') }}
                    </div>
                    @if ($assignment->policy_type === 'selected_days')
                        <div class="text-slate-600">
                            {{ collect($assignment->weekdays ?? [])->map(fn ($d) => __($weekdays[$d] ?? $d))->join(', ') }}
                        </div>
                    @endif
                    @if ($orgPolicy['bypass_geofence'] && $assignment->is_active)
                        <div class="mt-1 text-xs text-emerald-700">{{ __('Geofence exemption applies on matching WFH days.') }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No permanent or selected-day assignments.') }}</p>
            @endforelse
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Your requests') }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-slate-500">
                            <th class="py-2 pr-4">{{ __('Dates') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4">{{ __('Approval') }}</th>
                            <th class="py-2 pr-4">{{ __('Reason') }}</th>
                            <th class="py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $wfhRequest)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4">{{ $wfhRequest->dateLabel() }}</td>
                                <td class="py-2 pr-4">{{ __($statuses[$wfhRequest->status] ?? $wfhRequest->status) }}</td>
                                <td class="py-2 pr-4 text-slate-600">
                                    @forelse ($wfhRequest->approvalSteps as $step)
                                        <div>{{ __('Step') }} {{ $step->step_order }}: {{ __($stepStatuses[$step->status] ?? $step->status) }}</div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="py-2 pr-4">{{ $wfhRequest->reason ?: '—' }}</td>
                                <td class="py-2 space-y-1">
                                    @if (in_array($wfhRequest->status, ['draft', 'pending'], true))
                                        <form method="POST" action="{{ route('ess.wfh.destroy', $wfhRequest) }}" onsubmit="return confirm(@json(__('Withdraw this request?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">{{ __('Withdraw') }}</button>
                                        </form>
                                    @elseif ($wfhRequest->status === 'approved')
                                        @can('cancel', $wfhRequest)
                                            <form method="POST" action="{{ route('ess.wfh.cancel', $wfhRequest) }}" onsubmit="return confirm(@json(__('Cancel this approved WFH?')))">
                                                @csrf
                                                <button type="submit" class="text-amber-700 hover:underline">{{ __('Cancel') }}</button>
                                            </form>
                                        @else
                                            —
                                        @endcan
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">{{ __('No requests yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        </div>
    </div>
</x-app-layout>
