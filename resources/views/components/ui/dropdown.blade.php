@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-surface-card'])
@php
$alignment = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};
$width = ['48' => 'w-48', '56' => 'w-56', '64' => 'w-64'][$width] ?? 'w-48';
@endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">{{ $trigger }}</div>
    <div x-show="open" x-transition x-cloak @click="open = false" class="absolute z-dropdown mt-2 {{ $width }} rounded-lg shadow-md {{ $alignment }}">
        <div class="rounded-lg ring-1 ring-line {{ $contentClasses }}">{{ $content }}</div>
    </div>
</div>
