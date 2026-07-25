<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Priorities')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Priorities'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($priorities->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No task priorities defined yet.') }}</div>
        @else
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Level') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($priorities as $priority)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $priority->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $priority->level }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('task-priorities.edit', $priority) }}" class="text-primary-600 hover:underline">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-3">{{ $priorities->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
