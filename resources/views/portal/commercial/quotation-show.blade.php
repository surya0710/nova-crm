<x-layouts.portal>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ $quotation->number }}</h1>
        <a class="text-sm underline" href="{{ route('portal.commercial.quotations.pdf', [$portalOrganization, $quotation]) }}">{{ __('PDF') }}</a>
    </div>
    <p class="mb-4 text-sm text-slate-500">{{ $quotation->status_label }} · {{ number_format((float) $quotation->total, 2) }} {{ $quotation->currency }}</p>
    @if ($quotation->status === 'sent')
        <div class="mb-4 flex gap-2">
            <form method="POST" action="{{ route('portal.commercial.quotations.accept', [$portalOrganization, $quotation]) }}">@csrf<button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">{{ __('Accept') }}</button></form>
            <form method="POST" action="{{ route('portal.commercial.quotations.reject', [$portalOrganization, $quotation]) }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm">{{ __('Reject') }}</button></form>
        </div>
    @endif
    <table class="w-full text-sm">
        @foreach ($quotation->items as $item)
            <tr class="border-t"><td class="py-2">{{ $item->description }}</td><td class="py-2 text-right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
        @endforeach
    </table>
</x-layouts.portal>
