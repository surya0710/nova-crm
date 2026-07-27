@php
    $keys = array_keys(config('onboarding.steps', []));
    $idx = array_search($step, $keys, true);
    $prev = $idx > 0 ? $keys[$idx - 1] : null;
    $canSkip = ! in_array($step, ['organization', 'modules', 'go_live'], true);
@endphp

<div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-line pt-4">
    <div class="flex gap-2">
        @if ($prev)
            <form method="post" action="{{ route('platform.onboarding.previous', $onboarding) }}">
                @csrf
                <input type="hidden" name="to_step" value="{{ $prev }}">
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Previous') }}</x-ui.button>
            </form>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        <x-ui.button type="submit" form="onboarding-step-form" name="intent" value="draft" formaction="{{ route('platform.onboarding.draft', $onboarding) }}" variant="secondary" size="sm">
            {{ __('Save draft') }}
        </x-ui.button>
        @if ($canSkip)
            <button type="submit" form="onboarding-step-form" name="skip" value="1" class="rounded-md border border-line px-3 py-1.5 text-sm text-ink-muted hover:bg-surface-muted">
                {{ __('Skip for later') }}
            </button>
        @endif
        @if ($step === 'go_live')
            <x-ui.button type="submit" form="onboarding-finish-form" variant="primary" size="sm">{{ __('Finish & go live') }}</x-ui.button>
        @else
            <x-ui.button type="submit" form="onboarding-step-form" variant="primary" size="sm">{{ __('Save & continue') }}</x-ui.button>
        @endif
    </div>
</div>
