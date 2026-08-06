<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Offer Templates')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offer Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Templates') }}</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($templates as $template)
                    <li>
                        <a href="{{ route('hrms.recruitment.offer-templates.show', $template) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $template->name }}</a>
                        @if ($template->department) — {{ $template->department->name }} @endif
                        @if (! $template->is_active) <span class="text-slate-400">({{ __('Inactive') }})</span> @endif
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No offer templates yet.') }}</li>
                @endforelse
            </ul>
            {{ $templates->links() }}
        </div>
        @can('create', App\Models\OfferTemplate::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Create Template') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.offer-templates.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Name') }}</label><input name="name" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Department') }}</label>
                    <select name="department_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Any') }}</option>
                        @foreach ($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Designation') }}</label>
                    <select name="designation_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Any') }}</option>
                        @foreach ($designations as $des)<option value="{{ $des->id }}">{{ $des->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Employment Type') }}</label>
                    <select name="employment_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Any') }}</option>
                        @foreach ($employmentTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Template Content') }}</label>
                    <textarea name="template_content" rows="6" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>Dear {{ '{{candidate_name}}' }}, we are pleased to offer you the position of {{ '{{position}}' }} at a salary of {{ '{{salary}}' }}. Joining date: {{ '{{joining_date}}' }}. Benefits: {{ '{{benefits}}' }}.</textarea>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Placeholders: candidate_name, position, salary, variable_pay, joining_date, reporting_manager, benefits, expiry_date') }}</p>
                </div>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Create Template') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
