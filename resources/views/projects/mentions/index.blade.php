<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Mentions')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Mentions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="GET" action="{{ route('mentions.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3 items-end">
        <div>
            <x-input-label for="project_id" :value="__('Project ID')" />
            <x-text-input id="project_id" type="number" name="project_id" class="block mt-1" :value="request('project_id')" min="1" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 pb-2">
            <input type="checkbox" name="unread" value="1" @checked(request()->boolean('unread')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            {{ __('Unread only') }}
        </label>
        <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
        @if (request()->filled('project_id') || request()->boolean('unread'))
            <a href="{{ route('mentions.index') }}" class="text-sm text-slate-500 hover:text-slate-800 pb-2">{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($mentions->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No mentions found.') }}</div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($mentions as $mention)
                    @php
                        $mention->loadMissing(['project', 'task', 'mentionedBy']);
                    @endphp
                    <li class="px-6 py-4 flex items-start justify-between gap-4 {{ $mention->read_at ? '' : 'bg-indigo-50/40' }}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                @unless ($mention->read_at)
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">{{ __('Unread') }}</span>
                                @endunless
                                <p class="text-sm font-medium text-slate-900">
                                    {{ $mention->mentionedBy?->name ?? __('Someone') }}
                                    {{ __('mentioned you') }}
                                </p>
                            </div>
                            @if ($mention->excerpt)
                                <p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap">{{ $mention->excerpt }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-500">
                                @if ($mention->project)
                                    <a href="{{ route('projects.show', $mention->project) }}" class="text-primary-600 hover:text-primary-700">{{ $mention->project->name }}</a>
                                @endif
                                @if ($mention->task)
                                    · <a href="{{ route('tasks.show', $mention->task) }}" class="text-primary-600 hover:text-primary-700">{{ $mention->task->title }}</a>
                                @endif
                                · {{ $mention->created_at?->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
