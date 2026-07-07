<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Edit :label', ['label' => crm_term('invoice')]) }}</h1>
            <p class="text-sm text-slate-500">{{ $invoice->number }}</p>
        </div>
    </x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="max-w-6xl">
        @csrf @method('PUT')
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('invoices._form', ['invoice' => $invoice, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products, 'sourceQuotation' => null])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t bg-slate-50/50 flex justify-between">
                <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-slate-600">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
