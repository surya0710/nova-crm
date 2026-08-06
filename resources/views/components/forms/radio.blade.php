@props(['disabled' => false, 'label' => null])
<label class="inline-flex items-start gap-2 text-sm text-ink">
    <input type="radio" @disabled($disabled) {{ $attributes->merge(['class' => 'mt-0.5 border-line text-primary-600 shadow-sm focus:ring-primary-500']) }}>
    @if ($label)<span>{{ $label }}</span>@else<span>{{ $slot }}</span>@endif
</label>
