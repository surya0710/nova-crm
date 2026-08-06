@props(['columns' => 2])
<dl {{ $attributes->class([
    'grid gap-x-6 gap-y-5',
    'grid-cols-1' => $columns === 1,
    'grid-cols-1 sm:grid-cols-2' => $columns === 2,
    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' => $columns >= 3,
]) }}>
    {{ $slot }}
</dl>
