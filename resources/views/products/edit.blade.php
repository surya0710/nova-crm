<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Edit Product') }}</h1>
            <p class="text-sm text-slate-500">{{ $product->name }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <form method="POST" action="{{ route('products.update', $product) }}" class="max-w-4xl">
        @csrf
        @method('PATCH')
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('products._form', ['product' => $product])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
                <a href="{{ route('products.show', $product) }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
