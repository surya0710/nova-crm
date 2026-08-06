export function registerLeadFollowUpAlerts() {
    document.addEventListener('alpine:init', () => {
        Alpine.data('leadFollowUpAlerts', (config) => ({
            pollUrl: config.pollUrl,
            acknowledgeBase: config.acknowledgeBase,
            pollIntervalMs: config.pollIntervalMs ?? 10000,
            queue: [],
            seenIds: new Set(),
            current: null,
            open: false,
            timer: null,
            exactTimer: null,
            polling: false,
            disabled: false,

            init() {
                for (const lead of config.initial ?? []) {
                    this.enqueue(lead);
                }

                if (this.queue.length > 0) {
                    this.showNext();
                }

                this.check();
                this.timer = setInterval(() => this.check(), this.pollIntervalMs);
            },

            stopPolling() {
                this.disabled = true;
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                if (this.exactTimer) {
                    clearTimeout(this.exactTimer);
                    this.exactTimer = null;
                }
            },

            enqueue(lead) {
                if (! lead?.id || this.seenIds.has(lead.id) || this.current?.id === lead.id) {
                    return;
                }

                if (this.queue.some((item) => item.id === lead.id)) {
                    return;
                }

                this.queue.push(lead);
                this.scheduleExactCheck(lead);
            },

            scheduleExactCheck(lead) {
                if (! lead?.next_follow_up_at || this.disabled) {
                    return;
                }

                const dueAt = new Date(lead.next_follow_up_at).getTime();
                const delay = dueAt - Date.now();

                if (delay <= 0) {
                    return;
                }

                if (this.exactTimer) {
                    clearTimeout(this.exactTimer);
                }

                this.exactTimer = setTimeout(() => this.check(), delay + 500);
            },

            async check() {
                if (this.polling || this.disabled || ! this.pollUrl) {
                    return;
                }

                this.polling = true;

                try {
                    const response = await window.axios.get(this.pollUrl);
                    const due = response.data?.data ?? [];

                    for (const lead of due) {
                        this.enqueue(lead);
                    }

                    if (! this.open && this.queue.length > 0) {
                        this.showNext();
                    }
                } catch (error) {
                    const status = error?.response?.status;
                    // Permission / module / auth failures: stop permanently — do not spam the console.
                    if (status === 401 || status === 403 || status === 404) {
                        this.stopPolling();
                        return;
                    }
                    console.error('Follow-up poll failed', error);
                } finally {
                    this.polling = false;
                }
            },

            showNext() {
                if (this.queue.length === 0) {
                    this.current = null;
                    this.open = false;
                    document.body.classList.remove('overflow-y-hidden');
                    return;
                }

                this.current = this.queue.shift();
                this.seenIds.add(this.current.id);
                this.open = true;
                document.body.classList.add('overflow-y-hidden');
            },

            async acknowledgeLead(leadId) {
                try {
                    await window.axios.post(`${this.acknowledgeBase}/${leadId}/follow-up/acknowledge`);
                } catch (error) {
                    console.error('Follow-up acknowledge failed', error);
                }
            },

            closeCurrent() {
                this.current = null;
                this.open = false;
                document.body.classList.remove('overflow-y-hidden');
            },

            async dismiss() {
                if (! this.current) {
                    return;
                }

                const leadId = this.current.id;

                await this.acknowledgeLead(leadId);
                this.showNext();
            },

            async viewLead() {
                if (! this.current?.url) {
                    return;
                }

                const url = this.current.url;
                const leadId = this.current.id;

                this.seenIds.add(leadId);
                this.queue = this.queue.filter((item) => item.id !== leadId);
                this.closeCurrent();

                await this.acknowledgeLead(leadId);

                window.location.href = url;
            },
        }));
    });
}
