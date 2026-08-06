<x-careers-layout>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <aside class="lg:col-span-1 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-semibold">{{ __('About') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $settings?->about_us ?? $organization->description ?? __('Explore open roles and grow with us.') }}</p>
            </div>
            <form method="GET" class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                <h2 class="font-semibold">{{ __('Filters') }}</h2>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search jobs') }}" class="w-full rounded-lg border-slate-300">
                <select name="department_id" class="w-full rounded-lg border-slate-300">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="{{ __('Location') }}" class="w-full rounded-lg border-slate-300">
                <select name="employment_type" class="w-full rounded-lg border-slate-300">
                    <option value="">{{ __('All employment types') }}</option>
                    @foreach(config('hrms.employment_types', []) as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['employment_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Apply filters') }}</button>
            </form>
        </aside>
        <section class="lg:col-span-3 space-y-4">
            <h1 class="text-2xl font-semibold">{{ __('Open Positions') }} ({{ $openings->count() }})</h1>
            @forelse($openings as $opening)
                <a href="{{ route('careers.jobs.show', [$organization, $opening]) }}" class="block rounded-xl border border-slate-200 bg-white p-5 hover:border-indigo-300">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">{{ $opening->title }}</h2>
                            <p class="text-sm text-slate-500 mt-1">{{ $opening->department?->name }} · {{ config('hrms.employment_types.'.$opening->employment_type, $opening->employment_type) }} · {{ $opening->location ?? __('Remote/Flexible') }}</p>
                        </div>
                        <span class="text-sm text-slate-500">{{ $opening->publish_date?->format('M j, Y') }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">{{ __('No open positions match your search.') }}</div>
            @endforelse
        </section>
    </div>
</x-careers-layout>
