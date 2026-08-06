<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Feedback')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('My Feedback'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Campaign') }}</th>
                    <th class="p-3 text-left">{{ __('Subject') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Due') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($requests as $req)
                <tr class="border-t">
                    <td class="p-3">{{ $req->campaign?->name }}</td>
                    <td class="p-3">{{ $req->subjectEmployee?->first_name }} {{ $req->subjectEmployee?->last_name }}</td>
                    <td class="p-3">{{ $participantTypes[$req->participant_type] ?? $req->participant_type }}</td>
                    <td class="p-3">{{ $req->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="p-3">{{ $statuses[$req->status] ?? $req->status }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.feedback.requests.show', $req) }}">
                            @if (in_array($req->status, ['pending', 'started']))
                                {{ __('Complete') }}
                            @else
                                {{ __('View') }}
                            @endif
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">{{ __('No feedback requests assigned to you.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $requests->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
