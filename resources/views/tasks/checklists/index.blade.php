<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Checklists')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Checklists'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="rounded-xl bg-white border border-slate-200 p-5 max-w-2xl">
        <ul class="space-y-2 mb-4">
            @foreach ($checklists as $item)
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="{{ $item->is_completed ? 'line-through text-slate-400' : 'text-slate-800' }}">{{ $item->title }}</span>
                    <div class="flex gap-2">
                        @unless ($item->is_completed)
                            <form method="POST" action="{{ route('tasks.checklists.complete', [$task, $item]) }}">@csrf @method('PATCH')<button class="text-emerald-600 text-xs">{{ __('Complete') }}</button></form>
                        @endunless
                        <form method="POST" action="{{ route('tasks.checklists.destroy', [$task, $item]) }}">@csrf @method('DELETE')<button class="text-red-600 text-xs">{{ __('Delete') }}</button></form>
                    </div>
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('tasks.checklists.store', $task) }}" class="flex gap-2">
            @csrf
            <x-text-input name="title" class="w-full" placeholder="{{ __('Checklist item') }}" required />
            <x-primary-button>{{ __('Add') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
