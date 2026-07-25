<nav aria-label="{{ __('Documentation navigation') }}" class="lg:sticky lg:top-6 space-y-3">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Knowledge Center') }}</h2>
        <div class="space-y-2">
            @foreach ($navigationTree as $moduleItem)
                <div x-data="{ expanded: @js($moduleItem['expanded']) }">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded p-1 text-slate-500 hover:bg-slate-100"
                            @click="expanded = ! expanded"
                            :aria-expanded="expanded.toString()"
                            aria-label="{{ __('Toggle module pages') }}"
                        >
                            <span x-text="expanded ? '−' : '+'"></span>
                        </button>
                        <a
                            href="{{ $moduleItem['url'] }}"
                            class="flex-1 rounded-md px-2 py-1.5 text-sm {{ $moduleItem['active'] ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-100' }}"
                        >
                            {{ $moduleItem['title'] }}
                        </a>
                    </div>

                    <div x-show="expanded" x-cloak class="mt-1 ml-6 space-y-1 border-l border-slate-100 pl-2">
                        @foreach ($moduleItem['pages'] as $page)
                            <a
                                href="{{ $page['url'] }}"
                                class="block rounded-md px-2 py-1 text-xs {{ $page['active'] ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}"
                                @if ($page['active']) aria-current="page" @endif
                            >
                                {{ $page['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</nav>
