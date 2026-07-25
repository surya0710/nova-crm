<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Attachments')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Attachments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="max-w-2xl space-y-4">
        <ul class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-200">
            @forelse ($attachments as $attachment)
                <li class="px-4 py-3 flex items-center justify-between gap-3 text-sm">
                    <a href="{{ route('tasks.attachments.download', [$task, $attachment]) }}" class="text-primary-600 hover:underline">{{ $attachment->file_name }}</a>
                    <form method="POST" action="{{ route('tasks.attachments.destroy', [$task, $attachment]) }}">@csrf @method('DELETE')<button class="text-red-600 text-xs">{{ __('Delete') }}</button></form>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-slate-500">{{ __('No attachments yet.') }}</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="rounded-xl bg-white border border-slate-200 p-4 flex gap-3 items-center">
            @csrf
            <input type="file" name="file" required class="text-sm" />
            <x-primary-button>{{ __('Upload') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
