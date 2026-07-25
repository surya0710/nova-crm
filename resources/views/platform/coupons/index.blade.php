@php $canManage = auth('platform')->user()->hasPermission('platform.subscriptions.manage'); @endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Coupons')"
        :subtitle="__('Discount codes for subscription plans')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Coupons'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('platform.coupons.create')" variant="primary" size="sm">{{ __('New Coupon') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Search')" name="search" class="min-w-[16rem] flex-1">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Code or name…') }}" />
                </x-forms.field>
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($coupons->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No coupons yet')" :description="__('Create discount codes for subscription plans.')" :action-href="$canManage ? route('platform.coupons.create') : null" action-label="{{ __('New Coupon') }}" />
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Code') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Discount') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Plan') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Redemptions') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($coupons as $coupon)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $coupon->code }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $coupon->name }}</td>
                                    <td class="px-4 py-3 text-ink">
                                        @if ($coupon->type === 'percent')
                                            {{ number_format($coupon->value, 0) }}%
                                        @else
                                            {{ number_format($coupon->value, 2) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $coupon->applies_to_plan ? config('platform.plans.'.$coupon->applies_to_plan, $coupon->applies_to_plan) : __('Any') }}</td>
                                    <td class="px-4 py-3 text-ink-muted">
                                        {{ $coupon->redemptions }}{{ $coupon->max_redemptions ? ' / '.$coupon->max_redemptions : '' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :variant="$coupon->isRedeemable() ? 'success' : 'neutral'">
                                            {{ $coupon->is_active ? ($coupon->isRedeemable() ? __('Active') : __('Expired')) : __('Inactive') }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $coupons->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
