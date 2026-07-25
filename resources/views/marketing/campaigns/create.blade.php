<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Create Campaign')"
        :subtitle="__('Define budget, channels, and attribution')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Campaigns'), 'href' => route('marketing.campaigns.index')],
                ['label' => __('Create Campaign'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('marketing.campaigns.store') }}">
            @csrf
            @include('marketing.campaigns._form', ['campaign' => $campaign, 'statuses' => $statuses, 'channelOptions' => $channelOptions])
            <x-forms.footer :cancel-href="route('marketing.campaigns.index')" :submit-label="__('Create Campaign')" />
        </form>
    </x-layouts.create>
</x-app-layout>
