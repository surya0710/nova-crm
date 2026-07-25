<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Comments')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Comments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="max-w-2xl space-y-4">
        @foreach ($comments as $comment)
            <div class="rounded-xl bg-white border border-slate-200 p-4">
                <div class="text-xs text-slate-500 mb-1">{{ $comment->user?->name }} · {{ $comment->created_at?->diffForHumans() }}</div>
                <div class="text-sm text-slate-800 whitespace-pre-wrap">{{ $comment->comment }}</div>
            </div>
        @endforeach
        <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="rounded-xl bg-white border border-slate-200 p-4 space-y-3">
            @csrf
            <x-mention-autocomplete name="comment" required>{{ old('comment') }}</x-mention-autocomplete>
            <x-input-error :messages="$errors->get('comment')" />
            <x-primary-button>{{ __('Add comment') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
