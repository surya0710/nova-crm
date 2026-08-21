<x-layouts.portal>
    <h1 class="mb-4 text-xl font-semibold">{{ __('Quotations') }}</h1>
    <ul class="divide-y rounded-xl border border-slate-200 bg-white">
        @forelse ($quotations as $quotation)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="font-medium">{{ $quotation->number }}</div>
                    <div class="text-xs text-slate-500">{{ $quotation->status_label }} · {{ number_format((float) $quotation->total, 2) }} {{ $quotation->currency }}</div>
                </div>
                <a class="text-sm underline" href="{{ route('portal.commercial.quotations.show', [$portalOrganization, $quotation]) }}">{{ __('Open') }}</a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No quotations yet.') }}</li>
        @endforelse
    </ul>
</x-layouts.portal>
