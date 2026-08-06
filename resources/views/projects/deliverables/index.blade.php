<x-app-layout>
    <x-flash-messages />
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ __('Deliverables') }}</h1>
                    <p class="text-sm text-slate-500">{{ $project->name }}</p>
                </div>
                <a href="{{ route('projects.portal.clients', $project) }}" class="text-sm underline">{{ __('Client portal') }}</a>
            </div>

            <form method="POST" action="{{ route('projects.deliverables.store', $project) }}" class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                @csrf
                <h2 class="font-medium">{{ __('New deliverable') }}</h2>
                <input name="title" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Title') }}" required>
                <textarea name="description" rows="2" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Description') }}"></textarea>
                <input type="date" name="due_date" class="rounded-lg border-slate-300">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Create') }}</button>
            </form>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2">{{ __('Title') }}</th>
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Due') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliverables as $deliverable)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2">{{ $deliverable->title }}</td>
                                <td class="px-4 py-2">{{ $deliverable->status_label }}</td>
                                <td class="px-4 py-2">{{ $deliverable->due_date?->toDateString() ?? '—' }}</td>
                                <td class="px-4 py-2 text-right"><a class="underline" href="{{ route('projects.deliverables.show', [$project, $deliverable]) }}">{{ __('Open') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No deliverables yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $deliverables->links() }}</div>
        </div>
    </div>
</x-app-layout>
