<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Product Categories')"
        :subtitle="__('Organize the product and service catalog')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => __('Categories'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\ProductCategory::class)
                <x-ui.button :href="route('product-categories.create')" variant="primary" size="sm">{{ __('Add Category') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        @if ($categories->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="generic"
                    :title="__('No categories yet')"
                    :description="__('Create categories to group products and services.')"
                    :action-href="auth()->user()->can('create', App\Models\ProductCategory::class) ? route('product-categories.create') : null"
                    :action-label="__('Add Category')"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="[__('Name'), __('Products'), __('Status'), '']" :sticky="false">
                @foreach ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-ink-heading">{{ $category->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $category->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$category->is_active ? 'success' : 'neutral'">
                                {{ $category->is_active ? __('Active') : __('Inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $category)
                                <a href="{{ route('product-categories.edit', $category) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Edit') }}</a>
                            @endcan
                            @can('delete', $category)
                                <form method="POST" action="{{ route('product-categories.destroy', $category) }}" class="inline ms-3" onsubmit="return confirm('{{ __('Delete this category?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-danger">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($categories->hasPages())
            <x-slot:pagination>
                {{ $categories->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
