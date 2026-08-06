@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])
<div {{ $attributes->class(['space-y-1.5']) }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-ink-heading">
            {{ $label }}@if($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($hint)<p class="text-xs text-ink-muted">{{ $hint }}</p>@endif
    @if ($name)<x-input-error :messages="$errors->get($name)" class="mt-1" />@endif
</div>
