<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Sprints')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Sprints'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('tasks.board') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Board') }}</a>
            <a href="{{ route('tasks.backlog') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ __('Backlog') }}</a>
        </x-slot:actions>

        @can('create', App\Models\Sprint::class)
            <form method="POST" action="{{ route('sprints.store') }}" class="mb-6 rounded-xl bg-white border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                @csrf
                <x-text-input name="name" placeholder="{{ __('Sprint name') }}" class="w-full lg:col-span-2" required />
                <x-text-input name="goal" placeholder="{{ __('Goal') }}" class="w-full lg:col-span-2" />
                <select name="project_id" class="border-gray-300 rounded-md text-sm">
                    <option value="">{{ __('No project') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="border-gray-300 rounded-md text-sm">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-text-input type="date" name="start_date" class="w-full" />
                <x-text-input type="date" name="end_date" class="w-full" />
                <x-primary-button type="submit" class="lg:col-span-6 w-fit">{{ __('Create sprint') }}</x-primary-button>
            </form>
        @endcan

        <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
            @if ($sprints->isEmpty())
                <div class="p-12 text-center text-sm text-slate-500">{{ __('No sprints yet.') }}</div>
            @else
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Goal') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Dates') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sprints as $sprint)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $sprint->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $sprint->goal ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $sprint->start_date?->format('M j') ?? '—' }}
                                    →
                                    {{ $sprint->end_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $sprint->status_label }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('delete', $sprint)
                                        <form method="POST" action="{{ route('sprints.destroy', $sprint) }}" class="inline" onsubmit="return confirm('{{ __('Delete this sprint?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 text-xs">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
