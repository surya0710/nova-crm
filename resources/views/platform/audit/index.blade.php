<x-platform-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold">{{ __('Platform Audit Log') }}</h1>
    </x-slot>

    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('When') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Event') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Actor') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Organization') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Subject') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-slate-400">{{ $log->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-white">{{ $log->event }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $log->platformUser?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $log->organization?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $log->subject ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-platform-layout>
