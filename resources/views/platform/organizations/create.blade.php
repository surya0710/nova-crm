<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('New Organization') }}</h1>
            <a href="{{ route('platform.organizations.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Back to organizations') }}</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-900/40 border border-rose-700 text-rose-200 px-4 py-3 text-sm">
            <div class="font-medium mb-1">{{ __('Please fix the organization details.') }}</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.organizations.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="font-medium text-white">{{ __('Organization Details') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm text-slate-300 mb-1">{{ __('Name') }}</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                    </div>
                    <div>
                        <label for="slug" class="block text-sm text-slate-300 mb-1">{{ __('Slug') }}</label>
                        <input id="slug" name="slug" value="{{ old('slug') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm text-slate-300 mb-1">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                    </div>
                    <div>
                        <label for="phone" class="block text-sm text-slate-300 mb-1">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="website" class="block text-sm text-slate-300 mb-1">{{ __('Website') }}</label>
                        <input id="website" type="url" name="website" value="{{ old('website') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h2 class="font-medium text-white">{{ __('Plan And Defaults') }}</h2>

                <div>
                    <label for="plan" class="block text-sm text-slate-300 mb-1">{{ __('Plan') }}</label>
                    <select id="plan" name="plan" required class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan', 'starter') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm text-slate-300 mb-1">{{ __('Status') }}</label>
                    <select id="status" name="status" required class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="timezone" class="block text-sm text-slate-300 mb-1">{{ __('Timezone') }}</label>
                    <select id="timezone" name="timezone" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                        <option value="">{{ __('Use template/system default') }}</option>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="currency" class="block text-sm text-slate-300 mb-1">{{ __('Currency') }}</label>
                    <select id="currency" name="currency" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                        <option value="">{{ __('Use template/system default') }}</option>
                        @foreach ($currencies as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency') === $code)>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tax_name" class="block text-sm text-slate-300 mb-1">{{ __('Tax Name') }}</label>
                    <input id="tax_name" name="tax_name" value="{{ old('tax_name') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
            <h2 class="font-medium text-white">{{ __('Industry Template') }}</h2>
            <select name="template_version_id" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                <option value="">{{ __('No template') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->currentVersion->id }}" @selected((int) old('template_version_id') === $template->currentVersion->id)>
                        {{ $template->name }} · {{ __('Version :version', ['version' => $template->currentVersion->version]) }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500">{{ __('Template settings are copied into the organization during creation. The organization remains independent afterwards.') }}</p>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
            <h2 class="font-medium text-white">{{ __('Initial Owner') }}</h2>
            <p class="text-sm text-slate-500">{{ __('Optional. If the email already exists, the user is attached as organization owner and password is ignored.') }}</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="owner_name" class="block text-sm text-slate-300 mb-1">{{ __('Owner Name') }}</label>
                    <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                </div>
                <div>
                    <label for="owner_email" class="block text-sm text-slate-300 mb-1">{{ __('Owner Email') }}</label>
                    <input id="owner_email" type="email" name="owner_email" value="{{ old('owner_email') }}" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                </div>
                <div>
                    <label for="owner_password" class="block text-sm text-slate-300 mb-1">{{ __('Temporary Password') }}</label>
                    <input id="owner_password" type="password" name="owner_password" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('platform.organizations.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Cancel') }}</a>
            <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Create Organization') }}</button>
        </div>
    </form>
</x-platform-layout>
