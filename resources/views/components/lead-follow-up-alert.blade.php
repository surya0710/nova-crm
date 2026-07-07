@if (Auth::check() && Auth::user()->hasPermission('leads.view'))
    <div
        x-data="{
            pollUrl: @js(route('leads.follow-ups.due')),
            acknowledgeBase: @js(url('leads')),
            queue: [],
            current: null,
            open: false,
            timer: null,
            polling: false,
            init() {
                this.check();
                this.timer = setInterval(() => this.check(), 30000);
            },
            async check() {
                if (this.polling) return;
                this.polling = true;
                try {
                    const response = await window.axios.get(this.pollUrl);
                    const due = response.data.data || [];
                    for (const lead of due) {
                        if (! this.queue.some((item) => item.id === lead.id) && this.current?.id !== lead.id) {
                            this.queue.push(lead);
                        }
                    }
                    if (! this.open && this.queue.length > 0) {
                        this.showNext();
                    }
                } catch (error) {
                    console.error('Follow-up poll failed', error);
                } finally {
                    this.polling = false;
                }
            },
            showNext() {
                if (this.queue.length === 0) {
                    this.current = null;
                    this.open = false;
                    return;
                }
                this.current = this.queue.shift();
                this.open = true;
            },
            async dismiss() {
                if (! this.current) return;
                const leadId = this.current.id;
                try {
                    await window.axios.post(`${this.acknowledgeBase}/${leadId}/follow-up/acknowledge`);
                } catch (error) {
                    console.error('Follow-up acknowledge failed', error);
                }
                this.showNext();
            },
        }"
        x-init="init()"
        x-cloak
    >
        <div x-show="open" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0" style="display: none;">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/60"></div>

            <div class="relative flex min-h-full items-center justify-center">
                <div
                    x-show="open"
                    x-transition
                    class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                    @keydown.escape.window="dismiss()"
                >
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ __('Follow-up Reminder') }}</h3>
                                <p class="text-sm text-amber-100 mt-0.5">{{ __('It is time to follow up with this lead') }}</p>
                            </div>
                        </div>
                    </div>

                    <template x-if="current">
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lead') }}</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900" x-text="current.name"></p>
                                <p class="text-sm text-slate-500" x-show="current.company" x-text="current.company"></p>
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('Scheduled') }}</dt>
                                    <dd class="mt-1 font-medium text-slate-900" x-text="current.next_follow_up_at_formatted"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                                    <dd class="mt-1 text-slate-900" x-text="current.status"></dd>
                                </div>
                                <div x-show="current.phone">
                                    <dt class="text-xs text-slate-500">{{ __('Phone') }}</dt>
                                    <dd class="mt-1 text-slate-900" x-text="current.phone"></dd>
                                </div>
                                <div x-show="current.email">
                                    <dt class="text-xs text-slate-500">{{ __('Email') }}</dt>
                                    <dd class="mt-1 text-slate-900 break-all" x-text="current.email"></dd>
                                </div>
                                <div x-show="current.assigned_to">
                                    <dt class="text-xs text-slate-500">{{ __('Assigned To') }}</dt>
                                    <dd class="mt-1 text-slate-900" x-text="current.assigned_to"></dd>
                                </div>
                                <div x-show="current.priority">
                                    <dt class="text-xs text-slate-500">{{ __('Priority') }}</dt>
                                    <dd class="mt-1 text-slate-900" x-text="current.priority"></dd>
                                </div>
                            </dl>

                            <div x-show="current.next_follow_up_note" class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('Notes') }}</p>
                                <p class="mt-1 text-sm text-amber-900 whitespace-pre-wrap" x-text="current.next_follow_up_note"></p>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <a
                                    :href="current.url"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                                >
                                    {{ __('View Lead') }}
                                </a>
                                <button
                                    type="button"
                                    @click="dismiss()"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    {{ __('Dismiss') }}
                                </button>
                            </div>

                            <p x-show="queue.length > 0" class="text-xs text-center text-slate-500">
                                <span x-text="queue.length"></span> {{ __('more follow-up(s) waiting') }}
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endif
