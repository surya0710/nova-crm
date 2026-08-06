@php
    $matrixData = $matrix['matrix'] ?? [];
    $severityColor = fn (int $p, int $i) => match (true) {
        ($p * $i) >= 16 => 'bg-red-200 text-red-900 border-red-300',
        ($p * $i) >= 10 => 'bg-orange-100 text-orange-900 border-orange-200',
        ($p * $i) >= 5 => 'bg-amber-100 text-amber-900 border-amber-200',
        default => 'bg-emerald-50 text-emerald-900 border-emerald-200',
    };
@endphp

<div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-slate-900">{{ __('Risk Matrix') }}</h3>
        <p class="text-sm text-slate-500 mt-0.5">{{ __('Probability × Impact (open risks)') }}</p>
    </div>
    <div class="p-6 overflow-x-auto">
        <div class="inline-block min-w-full">
            <div class="grid grid-cols-[auto_repeat(5,minmax(3rem,1fr))] gap-1 text-center text-xs">
                <div></div>
                @for ($i = 1; $i <= 5; $i++)
                    <div class="font-semibold text-slate-500 py-2">{{ __('I') }}{{ $i }}</div>
                @endfor
                @for ($p = 5; $p >= 1; $p--)
                    <div class="font-semibold text-slate-500 flex items-center justify-center">{{ __('P') }}{{ $p }}</div>
                    @for ($i = 1; $i <= 5; $i++)
                        @php $count = $matrixData[$p][$i] ?? 0; @endphp
                        <div class="aspect-square flex items-center justify-center rounded border font-semibold {{ $severityColor($p, $i) }} {{ $count > 0 ? 'ring-2 ring-indigo-300' : '' }}">
                            {{ $count > 0 ? $count : '' }}
                        </div>
                    @endfor
                @endfor
            </div>
            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-50 border border-emerald-200"></span> {{ __('Low') }}</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-200"></span> {{ __('Medium') }}</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-orange-100 border border-orange-200"></span> {{ __('High') }}</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-200 border border-red-300"></span> {{ __('Critical') }}</span>
            </div>
        </div>
    </div>
</div>
