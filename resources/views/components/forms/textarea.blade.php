@props(['disabled' => false])
<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50']) }}>{{ $slot }}</textarea>
