<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Budgets')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Budgets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @can('manageBudgets', $project)
        <form method="POST" action="{{ route('projects.budgets.update', $project) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Planned Total') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($budget?->planned_total ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actual Total') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($budget?->actual_total ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Forecast Total') }}</p>
                    <p class="mt-1 text-2xl font-bold text-primary-600">{{ number_format($budget?->forecast_total ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Variance') }}</p>
                    <p class="mt-1 text-2xl font-bold {{ ($budget?->variance_total ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($budget?->variance_total ?? 0, 2) }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Budget Settings') }}</h3>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Budget Name')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $budget?->name ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="currency" :value="__('Currency')" />
                        <x-text-input id="currency" name="currency" class="block mt-1 w-full" maxlength="3" :value="old('currency', $budget?->currency ?? 'USD')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            @foreach (config('projects.budget_statuses') as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $budget?->status ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="2" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">{{ old('notes', $budget?->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Budget Line Items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Item') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Planned') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actual') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Forecast') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Variance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($budget?->items ?? [] as $index => $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}" />
                                        <input type="text" name="items[{{ $index }}][name]" value="{{ old('items.'.$index.'.name', $item->name) }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" name="items[{{ $index }}][planned]" value="{{ old('items.'.$index.'.planned', $item->planned) }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" name="items[{{ $index }}][actual]" value="{{ old('items.'.$index.'.actual', $item->actual) }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" name="items[{{ $index }}][forecast]" value="{{ old('items.'.$index.'.forecast', $item->forecast) }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ number_format($item->variance ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                @for ($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="text" name="items[{{ $i }}][name]" value="{{ old('items.'.$i.'.name', config('projects.default_budget_categories')[$i]['name'] ?? '') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" step="0.01" name="items[{{ $i }}][planned]" value="{{ old('items.'.$i.'.planned') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" step="0.01" name="items[{{ $i }}][actual]" value="{{ old('items.'.$i.'.actual') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" step="0.01" name="items[{{ $i }}][forecast]" value="{{ old('items.'.$i.'.forecast') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" />
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-400">—</td>
                                    </tr>
                                @endfor
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Save Budget') }}</x-primary-button>
            </div>
        </form>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Planned Total') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($budget?->planned_total ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actual Total') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($budget?->actual_total ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Forecast Total') }}</p>
                <p class="mt-1 text-2xl font-bold text-primary-600">{{ number_format($budget?->forecast_total ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Variance') }}</p>
                <p class="mt-1 text-2xl font-bold {{ ($budget?->variance_total ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($budget?->variance_total ?? 0, 2) }}</p>
            </div>
        </div>

        @if ($budget && $budget->items->isNotEmpty())
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Budget Line Items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Item') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Planned') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actual') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Forecast') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Variance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($budget->items as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $item->name }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($item->planned ?? 0, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($item->actual ?? 0, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($item->forecast ?? 0, 2) }}</td>
                                    <td class="px-6 py-4 text-sm {{ ($item->variance ?? 0) > 0 ? 'text-red-600' : 'text-slate-600' }}">{{ number_format($item->variance ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-12 text-center text-sm text-slate-500">
                {{ __('No budget workspace configured yet.') }}
            </div>
        @endif
    @endcan
    </x-layouts.entity-detail>
</x-app-layout>
