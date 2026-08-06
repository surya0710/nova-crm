@props([
    'columns' => [],
    'sticky' => true,
    'dense' => false,
])
<div {{ $attributes->except(['columns', 'sticky', 'dense'])->class(['overflow-x-auto rounded-xl border border-line bg-surface-card shadow-sm']) }}>
    <table @class([
        'min-w-full divide-y divide-line text-sm',
        '[&_th]:py-2 [&_td]:py-2' => $dense,
    ])>
        @if (count($columns))
            <thead @class([
                'bg-surface-muted',
                'sticky top-0 z-10 shadow-sm' => $sticky,
            ])>
                <tr>
                    @foreach ($columns as $column)
                        @php
                            $label = is_array($column) ? ($column['label'] ?? '') : $column;
                            $align = is_array($column) ? ($column['align'] ?? 'left') : 'left';
                            $class = is_array($column) ? ($column['class'] ?? '') : '';
                            $alignClass = match ($align) {
                                'right' => 'text-right',
                                'center' => 'text-center',
                                default => 'text-left',
                            };
                        @endphp
                        <th scope="col" @class(['px-4 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted', $alignClass, $class])>
                            {{ $label }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line bg-surface-card">{{ $slot }}</tbody>
    </table>
</div>
