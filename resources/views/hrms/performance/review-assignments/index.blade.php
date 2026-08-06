<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Review Assignments')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Review Assignments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PerformanceReviewAssignment::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Create Assignment') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.review-assignments.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="performance_cycle_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Performance Cycle') }}</option>
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}" @selected(old('performance_cycle_id') == $cycle->id)>{{ $cycle->name }}</option>
                @endforeach
            </select>
            <select name="employee_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>
            <select name="review_template_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Review Template') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('review_template_id') == $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
            <select name="review_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($reviewTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('review_type', 'manager') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="primary_reviewer_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Primary Reviewer (optional)') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('primary_reviewer_id') == $employee->id)>{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>
            <x-forms.input name="due_date" type="date" :value="old('due_date')"  />
            <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="assigned">{{ __('Assign immediately') }}</option>
                <option value="planned">{{ __('Save as planned') }}</option>
            </select>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Assignment') }}</x-ui.button>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Employee') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Reviewer') }}</th>
                    <th class="p-3 text-left">{{ __('Due') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($assignments as $assignment)
                <tr class="border-t">
                    <td class="p-3">{{ $assignment->employee?->first_name }} {{ $assignment->employee?->last_name }}</td>
                    <td class="p-3">{{ $reviewTypes[$assignment->review_type] ?? $assignment->review_type }}</td>
                    <td class="p-3">{{ $assignment->cycle?->name }}</td>
                    <td class="p-3">{{ $assignment->primaryReviewer?->first_name }} {{ $assignment->primaryReviewer?->last_name }}</td>
                    <td class="p-3">{{ $assignment->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="p-3">{{ $statuses[$assignment->status] ?? $assignment->status }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.review-assignments.show', $assignment) }}">{{ __('View') }}</a>
                        @if ($assignment->review)
                            · <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.reviews.show', $assignment->review) }}">{{ __('Review') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td class="p-3 text-slate-500" colspan="7">{{ __('No review assignments yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assignments->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
