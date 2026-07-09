<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('Industry Templates') }}</h1>
            @if (auth('platform')->user()->hasPermission('platform.industry_templates.create'))
                <a href="{{ route('platform.industry-templates.create') }}" class="text-sm rounded-lg bg-violet-600 hover:bg-violet-500 px-3 py-1.5">{{ __('New Template') }}</a>
            @endif
        </div>
    </x-slot>

    <form method="GET" class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search templates…') }}"
            class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm md:col-span-2" />
        <select name="status" class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm">
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="visibility" class="rounded-lg bg-slate-900 border-slate-700 text-white text-sm">
            <option value="">{{ __('All visibility') }}</option>
            @foreach ($visibilities as $value => $label)
                <option value="{{ $value }}" @selected(($filters['visibility'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Filter') }}</button>
    </form>

    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('Template') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Industry') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Visibility') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Current Version') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Applications') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($templates as $template)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-white font-medium">{{ $template->name }}</div>
                            <div class="text-xs text-slate-500">{{ $template->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $template->industry ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $template->statusLabel() }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $template->visibilityLabel() }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $template->currentVersion ? 'v'.$template->currentVersion->version : '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $template->applications_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('platform.industry-templates.show', $template) }}" class="text-violet-400 hover:text-violet-300">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('No industry templates yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $templates->links() }}</div>
</x-platform-layout>
