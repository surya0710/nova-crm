@php
    $ticket = $ticket ?? new App\Models\CustomerTicket(['status' => 'open', 'priority' => 'medium']);
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Ticket')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Subject')" name="subject" required>
                <x-forms.input id="subject" type="text" name="subject" :value="old('subject', $ticket->subject)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('customer_tickets.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Priority')" name="priority" required>
            <x-forms.select id="priority" name="priority" required>
                @foreach (config('customer_tickets.priorities') as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $ticket->priority) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Contact')" name="contact_id">
            <x-forms.select id="contact_id" name="contact_id">
                <option value="">{{ __('Not linked') }}</option>
                @foreach ($customer->contacts as $contact)
                    <option value="{{ $contact->id }}" @selected(old('contact_id', $ticket->contact_id) == $contact->id)>{{ $contact->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Assignee')" name="assigned_to">
            <x-forms.select id="assigned_to" name="assigned_to">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($assignees as $member)
                    <option value="{{ $member->id }}" @selected(old('assigned_to', $ticket->assigned_to) == $member->id)>{{ $member->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Due')" name="due_at">
            <x-forms.input id="due_at" type="datetime-local" name="due_at" :value="old('due_at', $ticket->due_at?->format('Y-m-d\\TH:i'))" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Details')" name="body">
                <x-forms.textarea id="body" name="body" rows="5">{{ old('body', $ticket->body) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>
</div>
