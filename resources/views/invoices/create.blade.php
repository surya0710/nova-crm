<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('New :label', ['label' => crm_term('invoice')]) }}</h1>
        </div>
    </x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('invoices.store') }}" class="max-w-6xl">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('invoices._form', ['invoice' => $invoice, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products, 'sourceQuotation' => $sourceQuotation ?? null])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t bg-slate-50/50 flex justify-between">
                <a href="{{ route('invoices.index') }}" class="text-sm text-slate-600">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Create :label', ['label' => crm_term('invoice')]) }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
