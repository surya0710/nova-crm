export function followUpDatetimeComponent(config) {
    return {
        date: config.date ?? '',
        time: config.time ?? '',
        minDate: config.minDate,
        minTime: config.minTime,

        get timeMin() {
            return this.date === this.minDate ? this.minTime : '';
        },

        get combined() {
            if (! this.date || ! this.time) {
                return '';
            }

            return `${this.date}T${this.time}`;
        },

        applyQuickPick(value) {
            if (! value || ! value.includes('T')) {
                return;
            }

            const [date, time] = value.split('T');
            this.date = date;
            this.time = time;
        },
    };
}

export function registerFollowUpDatetime() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('followUpDatetime', followUpDatetimeComponent);
    });
}
