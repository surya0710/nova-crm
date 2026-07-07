<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('New Quotation') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Build a quotation with line items for your customer') }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <form method="POST" action="{{ route('quotations.store') }}" class="max-w-6xl">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('quotations._form', ['quotation' => $quotation, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
                <a href="{{ route('quotations.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Create Quotation') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
