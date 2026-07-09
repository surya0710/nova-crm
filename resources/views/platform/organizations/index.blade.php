<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('Organizations') }}</h1>
            @if (auth('platform')->user()->hasPermission('platform.organizations.manage'))
                <a href="{{ route('platform.organizations.create') }}" class="text-sm rounded-lg bg-violet-600 hover:bg-violet-500 px-3 py-1.5">{{ __('New Organization') }}</a>
            @endif
        </div>
    </x-slot>

    <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search…') }}"
            class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm md:col-span-2" />
        <select name="status" class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm">
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="plan" class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm">
            <option value="">{{ __('All plans') }}</option>
            @foreach ($plans as $value => $label)
                <option value="{{ $value }}" @selected(($filters['plan'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Filter') }}</button>
    </form>

    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('Company') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Owner') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Plan') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Users') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Storage') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Created') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Last Activity') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($organizations as $organization)
                    @php $owner = $organization->owners->first(); @endphp
                    <tr>
                        <td class="px-4 py-3 text-white font-medium">{{ $organization->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $owner?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $organization->planLabel() }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-medium',
                                'bg-emerald-900/50 text-emerald-300' => $organization->isActive(),
                                'bg-amber-900/50 text-amber-300' => $organization->isSuspended(),
                                'bg-slate-700 text-slate-300' => $organization->isArchived(),
                            ])>{{ $organization->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $organization->users_count }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ number_format($organization->storage_used_bytes / 1048576, 1) }} MB</td>
                        <td class="px-4 py-3 text-slate-400">{{ $organization->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $organization->last_activity_at?->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('platform.organizations.show', $organization) }}" class="text-violet-400 hover:text-violet-300">{{ __('View') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $organizations->links() }}</div>
</x-platform-layout>
