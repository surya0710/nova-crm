<div class="mt-6 space-y-4">
    <ul class="space-y-2 text-sm">
        @foreach ($progress['checklist'] as $check)
            <li class="flex items-start gap-2 rounded-lg border border-line px-3 py-2">
                <span class="mt-0.5 font-semibold {{ $check['passed'] ? 'text-success' : 'text-ink-muted' }}">
                    {{ $check['passed'] ? '✓' : '○' }}
                </span>
                <div>
                    <p class="font-medium text-ink-heading">
                        {{ $check['label'] }}
                        @if ($check['required'])
                            <span class="text-xs text-danger">{{ __('required') }}</span>
                        @endif
                    </p>
                    @if (! empty($check['warning']) && ! $check['passed'])
                        <p class="text-xs text-ink-muted">{{ $check['warning'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    <form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}">
        @csrf
        <input type="hidden" name="step" value="go_live">
    </form>

    <form id="onboarding-finish-form" method="post" action="{{ route('platform.onboarding.finish', $onboarding) }}">
        @csrf
    </form>

    @include('platform.onboarding.partials.actions')
</div>
