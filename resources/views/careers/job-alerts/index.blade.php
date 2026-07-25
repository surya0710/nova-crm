<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('Job alerts') }}</h1>
    <form method="POST" action="{{ route('careers.job-alerts.store', $organization) }}" class="mt-4 rounded-xl border bg-white p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        @csrf
        <select name="department_id" class="rounded-lg border-slate-300"><option value="">{{ __('Any department') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
        <input name="location" placeholder="{{ __('Location') }}" class="rounded-lg border-slate-300">
        <input name="skills" placeholder="{{ __('Skills') }}" class="rounded-lg border-slate-300">
        <select name="employment_type" class="rounded-lg border-slate-300"><option value="">{{ __('Any type') }}</option>@foreach($employmentTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        <button class="md:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-white w-fit">{{ __('Create alert') }}</button>
    </form>
    <div class="mt-6 space-y-3">@forelse($alerts as $alert)
        <div class="rounded-xl border bg-white p-4 flex justify-between items-center">
            <div class="text-sm">{{ $alert->department?->name ?? __('Any department') }} · {{ $alert->location ?? __('Any location') }} · {{ $alert->skills ?? __('Any skills') }}</div>
            <form method="POST" action="{{ route('careers.job-alerts.destroy', [$organization, $alert]) }}">@csrf @method('DELETE')<button class="text-red-600 text-sm">{{ __('Remove') }}</button></form>
        </div>
    @empty<p class="text-slate-500">{{ __('No job alerts configured.') }}</p>@endforelse</div>
</x-careers-layout>
