<x-careers-layout>
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">{{ $opening->title }}</h1>
                <p class="text-slate-500 mt-1">{{ $opening->department?->name }} · {{ config('hrms.employment_types.'.$opening->employment_type, $opening->employment_type) }} · {{ $opening->location ?? __('Flexible') }}</p>
            </div>
            @auth('candidate')
                @if($isSaved)
                    <form method="POST" action="{{ route('careers.saved-jobs.destroy', [$organization, $opening]) }}">@csrf @method('DELETE')<button class="rounded-lg border px-3 py-2 text-sm">{{ __('Unsave') }}</button></form>
                @else
                    <form method="POST" action="{{ route('careers.saved-jobs.store', [$organization, $opening]) }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm">{{ __('Save job') }}</button></form>
                @endif
            @endauth
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><span class="text-slate-500">{{ __('Experience') }}</span><div class="font-medium">{{ $opening->experience ?? '—' }}</div></div>
            <div><span class="text-slate-500">{{ __('Education') }}</span><div class="font-medium">{{ $opening->education ?? '—' }}</div></div>
            <div><span class="text-slate-500">{{ __('Salary') }}</span><div class="font-medium">@if($opening->salary_range_min){{ number_format($opening->salary_range_min) }} - {{ number_format($opening->salary_range_max) }}@else{{ __('Not disclosed') }}@endif</div></div>
        </div>

        @if($opening->description)<section class="mt-6"><h2 class="font-semibold">{{ __('Description') }}</h2><p class="mt-2 text-slate-700 whitespace-pre-line">{{ $opening->description }}</p></section>@endif
        @if($opening->responsibilities)<section class="mt-6"><h2 class="font-semibold">{{ __('Responsibilities') }}</h2><p class="mt-2 text-slate-700 whitespace-pre-line">{{ $opening->responsibilities }}</p></section>@endif
        @if($opening->requirements)<section class="mt-6"><h2 class="font-semibold">{{ __('Requirements') }}</h2><p class="mt-2 text-slate-700 whitespace-pre-line">{{ $opening->requirements }}</p></section>@endif
        @if($settings?->benefits)<section class="mt-6"><h2 class="font-semibold">{{ __('Benefits') }}</h2><p class="mt-2 text-slate-700 whitespace-pre-line">{{ $settings->benefits }}</p></section>@endif

        <section class="mt-8 border-t pt-6">
            <h2 class="text-lg font-semibold">{{ __('Apply for this role') }}</h2>
            @auth('candidate')
                <form method="POST" action="{{ route('careers.jobs.apply', [$organization, $opening]) }}" class="mt-4 flex flex-wrap gap-3">
                    @csrf
                    <button name="draft" value="0" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Submit application') }}</button>
                    <button name="draft" value="1" class="rounded-lg border px-4 py-2">{{ __('Save draft') }}</button>
                </form>
            @elseif(!$portalSettings || $portalSettings->allow_guest_apply)
                <form method="POST" action="{{ route('careers.jobs.apply.guest', [$organization, $opening]) }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <input name="first_name" placeholder="{{ __('First name') }}" class="rounded-lg border-slate-300" required>
                    <input name="last_name" placeholder="{{ __('Last name') }}" class="rounded-lg border-slate-300" required>
                    <input name="email" type="email" placeholder="{{ __('Email') }}" class="rounded-lg border-slate-300 md:col-span-2" required>
                    <input name="phone" placeholder="{{ __('Phone') }}" class="rounded-lg border-slate-300 md:col-span-2">
                    <input name="resume" type="file" accept=".pdf,.doc,.docx" class="md:col-span-2" required>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white md:col-span-2">{{ __('Apply without account') }}</button>
                </form>
                <p class="mt-3 text-sm text-slate-500"><a href="{{ route('careers.register', $organization) }}" class="text-indigo-600">{{ __('Create an account') }}</a> {{ __('to track your application.') }}</p>
            @else
                <p class="mt-3 text-slate-600">{{ __('Please') }} <a href="{{ route('careers.login', $organization) }}" class="text-indigo-600">{{ __('log in') }}</a> {{ __('to apply.') }}</p>
            @endauth
        </section>
    </div>
</x-careers-layout>
