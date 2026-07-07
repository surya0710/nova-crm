<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Edit Quotation') }}</h1>
            <p class="text-sm text-slate-500">{{ $quotation->number }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <form method="POST" action="{{ route('quotations.update', $quotation) }}" class="max-w-6xl">
        @csrf
        @method('PUT')
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('quotations._form', ['quotation' => $quotation, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
                <a href="{{ route('quotations.show', $quotation) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save Quotation') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
