@props([
    'textareaId' => null,
    'name' => 'comment',
    'placeholder' => null,
])

@php
    $textareaId = $textareaId ?: 'mention-input-'.uniqid();
    $placeholder = $placeholder ?: __('Type @ to mention someone…');
@endphp

<div
    class="relative"
    x-data="{
        endpoint: @js(route('mentions.autocomplete')),
        open: false,
        results: [],
        highlighted: 0,
        query: '',
        mentionStart: -1,
        controller: null,
        onInput() {
            const el = this.$refs.textarea;
            const value = el.value;
            const caret = el.selectionStart ?? value.length;
            const before = value.slice(0, caret);
            const match = before.match(/(^|\s)@([a-zA-Z0-9._-]*)$/);
            if (!match) { this.close(); return; }
            this.mentionStart = caret - match[2].length - 1;
            this.query = match[2];
            this.fetchResults(this.query);
        },
        async fetchResults(q) {
            if (this.controller) this.controller.abort();
            this.controller = new AbortController();
            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('q', q || '');
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Failed');
                const json = await res.json();
                this.results = json.data || [];
                this.highlighted = 0;
                this.open = this.results.length > 0;
            } catch (e) {
                if (e.name !== 'AbortError') { this.results = []; this.open = false; }
            }
        },
        choose(item) {
            const el = this.$refs.textarea;
            const value = el.value;
            const caret = el.selectionStart ?? value.length;
            const before = value.slice(0, this.mentionStart);
            const after = value.slice(caret);
            const insertion = '@' + item.handle + ' ';
            el.value = before + insertion + after;
            const nextCaret = (before + insertion).length;
            el.focus();
            el.setSelectionRange(nextCaret, nextCaret);
            this.close();
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },
        highlightNext() {
            if (!this.open || !this.results.length) return;
            this.highlighted = (this.highlighted + 1) % this.results.length;
        },
        highlightPrev() {
            if (!this.open || !this.results.length) return;
            this.highlighted = (this.highlighted - 1 + this.results.length) % this.results.length;
        },
        selectHighlighted(event) {
            if (!this.open || !this.results.length) return;
            event.preventDefault();
            this.choose(this.results[this.highlighted]);
        },
        close() {
            this.open = false;
            this.results = [];
            this.mentionStart = -1;
        },
    }"
>
    <textarea
        id="{{ $textareaId }}"
        name="{{ $name }}"
        rows="3"
        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
        placeholder="{{ $placeholder }}"
        x-ref="textarea"
        @input="onInput()"
        @keydown.down.prevent="highlightNext()"
        @keydown.up.prevent="highlightPrev()"
        @keydown.enter="selectHighlighted($event)"
        @keydown.escape="close()"
        {{ $attributes }}
    >{{ $slot }}</textarea>

    <div
        x-show="open && results.length"
        x-cloak
        class="absolute z-30 mt-1 w-full max-h-56 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <template x-for="(item, index) in results" :key="item.id">
            <button
                type="button"
                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 flex items-center justify-between gap-2"
                :class="index === highlighted ? 'bg-slate-100' : ''"
                @click="choose(item)"
                @mouseenter="highlighted = index"
            >
                <span class="font-medium text-slate-900" x-text="item.name"></span>
                <span class="text-xs text-slate-500" x-text="'@' + item.handle"></span>
            </button>
        </template>
    </div>
</div>
