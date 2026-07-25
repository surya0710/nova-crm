@props([
    'title' => null,
    'actions' => [],
])
<section {{ $attributes->class(['space-y-3']) }} aria-label="{{ $title ?? __('Quick actions') }}">
    @if ($title)
        <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
    @endif
    <div class="flex flex-wrap gap-2">
        @foreach ($actions as $action)
            @continue(empty($action['href']))
            <x-ui.button
                :href="$action['href']"
                :variant="$action['variant'] ?? 'secondary'"
                size="sm"
            >{{ $action['label'] }}</x-ui.button>
        @endforeach
        {{ $slot }}
    </div>
</section>
