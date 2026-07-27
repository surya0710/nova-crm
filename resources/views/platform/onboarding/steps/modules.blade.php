<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="modules">

    <x-forms.field :label="__('Plan')" name="plan">
        <x-forms.select name="plan">
            @foreach ($plans as $value => $label)
                <option value="{{ $value }}" @selected(old('plan', $stepData['plan'] ?? $onboarding->organization?->plan ?? 'starter') === $value)>{{ $label }}</option>
            @endforeach
        </x-forms.select>
    </x-forms.field>

    <div>
        <p class="mb-2 text-sm font-medium text-ink-heading">{{ __('Modules') }}</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($selectableModules as $moduleKey)
                <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                    <input
                        type="checkbox"
                        name="modules[]"
                        value="{{ $moduleKey }}"
                        class="rounded border-line text-primary-600"
                        @checked(in_array($moduleKey, old('modules', $stepData['modules'] ?? $enabledModules), true))
                    >
                    <span>{{ $moduleLabels[$moduleKey] ?? ucfirst($moduleKey) }}</span>
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-ink-muted">{{ __('Unavailable modules for the selected plan are ignored by licensing sync.') }}</p>
    </div>

    @include('platform.onboarding.partials.actions')
</form>
