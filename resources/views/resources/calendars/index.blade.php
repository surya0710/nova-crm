<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Calendars')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Calendars'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($calendars->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No resource calendars yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Hours/day') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Effective') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($calendars as $calendar)
                            <tr>
                                <td class="px-4 py-3 text-slate-800">{{ $calendar->employee?->full_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $calendar->working_hours_per_day }}</td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ $calendar->effective_from?->toDateString() }}
                                    @if ($calendar->effective_to)
                                        → {{ $calendar->effective_to->toDateString() }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    @can('update', $calendar)
                                        <a href="{{ route('resources.calendars.edit', $calendar) }}" class="text-primary-600 hover:text-primary-700">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $calendar)
                                        <form action="{{ route('resources.calendars.destroy', $calendar) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Delete this calendar?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $calendars->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
