<x-platform-layout>
    <x-layouts.create
        :title="__('New Support Ticket')"
        :subtitle="__('Create a support ticket for a tenant or external requester')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Support'), 'href' => route('platform.support.index')],
                ['label' => __('Tickets'), 'href' => route('platform.support.tickets')],
                ['label' => __('New Ticket'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.support.tickets.store') }}" class="space-y-6">
            @csrf

            <x-forms.section :title="__('Ticket Details')">
                <x-forms.field :label="__('Subject')" name="subject" required class="sm:col-span-2">
                    <x-forms.input name="subject" value="{{ old('subject') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Organization')" name="organization_id">
                    <x-forms.select name="organization_id">
                        <option value="">{{ __('None / external') }}</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected((int) old('organization_id') === $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Assignee')" name="assignee_id">
                    <x-forms.select name="assignee_id">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((int) old('assignee_id') === $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'open') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Priority')" name="priority">
                    <x-forms.select name="priority">
                        @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Category')" name="category">
                    <x-forms.input name="category" value="{{ old('category', 'general') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Body')" name="body" class="sm:col-span-2">
                    <x-forms.textarea name="body" rows="5">{{ old('body') }}</x-forms.textarea>
                </x-forms.field>
            </x-forms.section>

            <x-forms.section :title="__('Requester')" :subtitle="__('Optional external requester details')">
                <x-forms.field :label="__('Requester Name')" name="requester_name">
                    <x-forms.input name="requester_name" value="{{ old('requester_name') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Requester Email')" name="requester_email">
                    <x-forms.input type="email" name="requester_email" value="{{ old('requester_email') }}" />
                </x-forms.field>
            </x-forms.section>

            <x-forms.footer :cancel-href="route('platform.support.tickets')" :submit-label="__('Create Ticket')" />
        </form>
    </x-layouts.create>
</x-platform-layout>
