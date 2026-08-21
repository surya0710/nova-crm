<x-layouts.portal>
    <h1 class="mb-4 text-xl font-semibold">{{ __('Credit and debit notes') }}</h1>
    <ul class="divide-y rounded-xl border border-slate-200 bg-white">
        @forelse ($notes as $note)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="font-medium">{{ $note->number }} · {{ $note->type_label }}</div>
                    <div class="text-xs text-slate-500">{{ $note->formatted_total }} · {{ $note->status_label }}</div>
                </div>
                <a class="text-sm underline" href="{{ route('portal.commercial.notes.pdf', [$portalOrganization, $note]) }}">{{ __('PDF') }}</a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No notes yet.') }}</li>
        @endforelse
    </ul>
</x-layouts.portal>
