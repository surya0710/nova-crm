@php
    $statusColors = [
        'pending' => '#94a3b8',
        'in_progress' => '#0ea5e9',
        'completed' => '#22c55e',
        'cancelled' => '#64748b',
        'active' => '#22c55e',
        'delayed' => '#ef4444',
        'on_hold' => '#f59e0b',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Gantt')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Gantt'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div
        x-data="{
            items: @js($ganttItems),
            zoom: 1,
            filterType: 'all',
            statusColors: @js($statusColors),
            get filteredItems() {
                if (this.filterType === 'all') return this.items;
                return this.items.filter(i => i.type === this.filterType);
            },
            get dateRange() {
                const dates = this.filteredItems.flatMap(i => [i.start, i.end]).filter(Boolean);
                if (!dates.length) return { min: new Date(), max: new Date(), days: 1 };
                const parsed = dates.map(d => new Date(d + 'T00:00:00'));
                const min = new Date(Math.min(...parsed));
                const max = new Date(Math.max(...parsed));
                const days = Math.max(1, Math.ceil((max - min) / 86400000) + 1);
                return { min, max, days };
            },
            barStyle(item) {
                const { min, days } = this.dateRange;
                const start = new Date(item.start + 'T00:00:00');
                const end = new Date(item.end + 'T00:00:00');
                const offset = Math.max(0, Math.ceil((start - min) / 86400000));
                const duration = Math.max(1, Math.ceil((end - start) / 86400000) + 1);
                const dayWidth = 28 * this.zoom;
                return {
                    marginLeft: (offset * dayWidth) + 'px',
                    width: (duration * dayWidth) + 'px',
                    backgroundColor: this.statusColors[item.status] || item.color || '#6366f1',
                };
            },
            dayHeaders() {
                const { min, days } = this.dateRange;
                const headers = [];
                for (let i = 0; i < days; i++) {
                    const d = new Date(min);
                    d.setDate(d.getDate() + i);
                    headers.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
                }
                return headers;
            }
        }"
        class="space-y-4"
    >
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-slate-500">{{ __('Zoom') }}</label>
                <input type="range" min="0.5" max="2" step="0.25" x-model.number="zoom" class="w-32" />
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-slate-500">{{ __('Filter') }}</label>
                <select x-model="filterType" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                    <option value="all">{{ __('All') }}</option>
                    <option value="project">{{ __('Project') }}</option>
                    <option value="milestone">{{ __('Milestones') }}</option>
                    <option value="task">{{ __('Tasks') }}</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-slate-500 ml-auto">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500"></span> {{ __('Completed') }}</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-sky-500"></span> {{ __('In Progress') }}</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-indigo-500"></span> {{ __('Milestone') }}</span>
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <template x-if="filteredItems.length === 0">
                <div class="p-12 text-center text-sm text-slate-500">{{ __('No schedulable items for this project.') }}</div>
            </template>
            <template x-if="filteredItems.length > 0">
                <div class="overflow-x-auto">
                    <div class="min-w-max p-4">
                        <div class="flex border-b border-slate-100 pb-2 mb-2 ml-48">
                            <template x-for="(label, idx) in dayHeaders()" :key="idx">
                                <div class="text-[10px] text-slate-400 shrink-0 text-center" :style="'width:' + (28 * zoom) + 'px'" x-text="label"></div>
                            </template>
                        </div>
                        <template x-for="item in filteredItems" :key="item.id">
                            <div class="flex items-center gap-4 py-2 border-b border-slate-50 last:border-0">
                                <div class="w-44 shrink-0 pr-2">
                                    <p class="text-xs font-medium text-slate-900 truncate" x-text="item.name"></p>
                                    <p class="text-[10px] text-slate-400 capitalize" x-text="item.type"></p>
                                </div>
                                <div class="flex-1 relative h-8 bg-slate-50 rounded">
                                    <div
                                        class="absolute top-1 h-6 rounded opacity-90 flex items-center px-1"
                                        :style="barStyle(item)"
                                        :title="item.name + ' (' + item.progress + '%)'"
                                    >
                                        <div class="h-1.5 bg-white/50 rounded-full flex-1 mx-1 overflow-hidden">
                                            <div class="h-full bg-white rounded-full" :style="'width:' + item.progress + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-10 shrink-0 text-xs font-medium text-slate-600 text-right" x-text="item.progress + '%'"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
