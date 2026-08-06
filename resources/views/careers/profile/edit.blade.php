<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('My profile') }}</h1>
    <form method="POST" action="{{ route('careers.profile.update', $organization) }}" enctype="multipart/form-data" class="mt-6 rounded-xl border bg-white p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf @method('PUT')
        <div><label class="text-sm">{{ __('First name') }}</label><input name="first_name" value="{{ old('first_name', $candidate->first_name) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div><label class="text-sm">{{ __('Last name') }}</label><input name="last_name" value="{{ old('last_name', $candidate->last_name) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div><label class="text-sm">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $candidate->phone) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div><label class="text-sm">{{ __('Experience') }}</label><input name="experience" value="{{ old('experience', $candidate->experience) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div class="md:col-span-2"><label class="text-sm">{{ __('Address') }}</label><input name="address" value="{{ old('address', $candidate->address) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div><label class="text-sm">{{ __('LinkedIn') }}</label><input name="linkedin" value="{{ old('linkedin', $candidate->linkedin) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div><label class="text-sm">{{ __('GitHub') }}</label><input name="github" value="{{ old('github', $candidate->github) }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
        <div class="md:col-span-2"><label class="text-sm">{{ __('Skills') }}</label><textarea name="skills" class="mt-1 w-full rounded-lg border-slate-300">{{ old('skills', $candidate->skills) }}</textarea></div>
        <div class="md:col-span-2"><label class="text-sm">{{ __('Profile photo') }}</label><input type="file" name="profile_photo" accept="image/*" class="mt-1"></div>
        <div class="md:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save profile') }}</button></div>
    </form>
</x-careers-layout>
