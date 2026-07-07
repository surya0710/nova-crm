@props([
    'attachableType',
    'attachableId',
    'attachments',
    'canUpload' => false,
    'canDelete' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden']) }}>
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-slate-900">{{ __('Files') }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('Documents attached to this record') }}</p>
        </div>
    </div>
    <div class="p-6 space-y-4">
        @if ($canUpload)
            <form method="POST" action="{{ route('attachments.store') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
                <input type="hidden" name="attachable_id" value="{{ $attachableId }}">
                <input type="file" name="file" required class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <x-primary-button type="submit" class="shrink-0">{{ __('Upload') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('file')" class="-mt-2" />
        @endif

        @if ($attachments->isEmpty())
            <p class="text-sm text-slate-500 text-center py-4">{{ __('No files attached yet.') }}</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($attachments as $attachment)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('attachments.download', $attachment) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 truncate block">
                                {{ $attachment->original_name }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $attachment->formatted_size }} · {{ $attachment->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($canDelete)
                            <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" onsubmit="return confirm('{{ __('Delete this file?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
