<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Offers')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offers'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <form method="GET" class="mb-4 flex gap-2">
                <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-1 bg-slate-100 rounded-md text-sm">{{ __('Filter') }}</button>
            </form>
            <ul class="space-y-2 text-sm">
                @forelse ($offers as $offer)
                    <li>
                        <a href="{{ route('hrms.recruitment.offers.show', $offer) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $offer->candidate?->fullName() }}</a>
                        — {{ $offer->jobApplication?->jobOpening?->title }} · {{ $offer->statusLabel() }}
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No offers yet.') }}</li>
                @endforelse
            </ul>
            {{ $offers->links() }}
        </div>
        @can('create', App\Models\OfferLetter::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Generate Offer') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.offers.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Application') }}</label>
                    <select name="job_application_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($applications as $app)<option value="{{ $app->id }}">{{ $app->candidate?->fullName() }} — {{ $app->jobOpening?->title }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Template') }}</label>
                    <select name="offer_template_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Default') }}</option>
                        @foreach ($templates as $tpl)<option value="{{ $tpl->id }}">{{ $tpl->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Proposed Salary') }}</label><input type="number" step="0.01" name="proposed_salary" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Variable Pay') }}</label><input type="number" step="0.01" name="variable_pay" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm text-slate-600">{{ __('Benefits') }}</label><textarea name="benefits" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2"></textarea></div>
                <div><label class="text-sm text-slate-600">{{ __('Joining Date') }}</label><input type="date" name="joining_date" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Expiry Date') }}</label><input type="date" name="expiry_date" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Generate Offer') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
