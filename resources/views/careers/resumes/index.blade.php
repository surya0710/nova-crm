<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('My resumes') }}</h1>
    <form method="POST" action="{{ route('careers.resumes.store', $organization) }}" enctype="multipart/form-data" class="mt-4 rounded-xl border bg-white p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        @csrf
        <input name="name" placeholder="{{ __('Resume name') }}" class="rounded-lg border-slate-300" required>
        <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> {{ __('Set as default') }}</label>
        <button class="md:col-span-3 rounded-lg bg-indigo-600 px-4 py-2 text-white w-fit">{{ __('Upload resume') }}</button>
    </form>
    <div class="mt-6 space-y-3">@forelse($resumes as $resume)
        <div class="rounded-xl border bg-white p-4 flex items-center justify-between">
            <div><div class="font-medium">{{ $resume->name }}</div><div class="text-sm text-slate-500">{{ $resume->original_name }} · {{ $resume->uploaded_at?->format('M j, Y') }}</div></div>
            <div class="flex gap-2">@if(!$resume->is_default)<form method="POST" action="{{ route('careers.resumes.default', [$organization, $resume]) }}">@csrf<button class="text-sm border rounded px-2 py-1">{{ __('Make default') }}</button></form>@else<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ __('Default') }}</span>@endif
            <form method="POST" action="{{ route('careers.resumes.destroy', [$organization, $resume]) }}">@csrf @method('DELETE')<button class="text-sm text-red-600">{{ __('Delete') }}</button></form></div>
        </div>
    @empty<p class="text-slate-500">{{ __('No resumes uploaded yet.') }}</p>@endforelse</div>
</x-careers-layout>
