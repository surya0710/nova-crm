<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Careers Site Settings')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Careers Site Settings'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <p class="text-sm text-slate-600 mb-4">{{ __('Public URL') }}: <a href="{{ route('careers.home', $organization) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800" target="_blank">{{ route('careers.home', $organization) }}</a></p>
        <form method="POST" action="{{ route('hrms.recruitment.careers.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div><label class="text-sm">{{ __('About us') }}</label><textarea name="about_us" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="4">{{ old('about_us', $settings->about_us) }}</textarea></div>
            <div><label class="text-sm">{{ __('Benefits') }}</label><textarea name="benefits" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3">{{ old('benefits', $settings->benefits) }}</textarea></div>
            <div><label class="text-sm">{{ __('Culture') }}</label><textarea name="culture" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3">{{ old('culture', $settings->culture) }}</textarea></div>
            <div><label class="text-sm">{{ __('Mission') }}</label><textarea name="mission" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3">{{ old('mission', $settings->mission) }}</textarea></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm">{{ __('Recruitment contact email') }}</label><input name="recruitment_contact_email" value="{{ old('recruitment_contact_email', $settings->recruitment_contact_email) }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm">{{ __('Recruitment contact phone') }}</label><input name="recruitment_contact_phone" value="{{ old('recruitment_contact_phone', $settings->recruitment_contact_phone) }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm">{{ __('SEO title') }}</label><input name="seo_title" value="{{ old('seo_title', $settings->seo_title) }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm">{{ __('SEO keywords') }}</label><input name="seo_keywords" value="{{ old('seo_keywords', $settings->seo_keywords) }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
            </div>
            <div><label class="text-sm">{{ __('SEO description') }}</label><textarea name="seo_description" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2">{{ old('seo_description', $settings->seo_description) }}</textarea></div>
            <div><label class="text-sm">{{ __('Custom footer') }}</label><textarea name="custom_footer" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2">{{ old('custom_footer', $settings->custom_footer) }}</textarea></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm">{{ __('Logo') }}</label><input type="file" name="logo" accept="image/*" class="mt-1"></div>
                <div><label class="text-sm">{{ __('Banner') }}</label><input type="file" name="banner" accept="image/*" class="mt-1"></div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $settings->is_published))> {{ __('Publish careers site') }}</label>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save settings') }}</button>
        </form>
    </div>
    </x-layouts.edit>
</x-app-layout>
