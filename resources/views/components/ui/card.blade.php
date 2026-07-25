@props(['padding' => true])
<div {{ $attributes->class(['rounded-xl border border-line bg-surface-card shadow-sm', $padding ? 'p-5' : '']) }}>
    @isset($header)
        <div class="mb-4 flex items-start justify-between gap-3">{{ $header }}</div>
    @endisset
    {{ $slot }}
    @isset($footer)
        <div class="mt-4 border-t border-line pt-4">{{ $footer }}</div>
    @endisset
</div>
