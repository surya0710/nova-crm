@props(['lines' => 3])
<div {{ $attributes->class(['animate-pulse space-y-3']) }} aria-hidden="true">
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-3 rounded bg-neutral-200/80" style="width: {{ max(40, 100 - ($i * 12)) }}%"></div>
    @endfor
</div>
