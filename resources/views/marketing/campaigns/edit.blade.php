<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Campaign')"
        :subtitle="$campaign->name"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Campaigns'), 'href' => route('marketing.campaigns.index')],
                ['label' => $campaign->name, 'href' => route('marketing.campaigns.show', $campaign)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('marketing.campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            @include('marketing.campaigns._form', ['campaign' => $campaign, 'statuses' => $statuses, 'channelOptions' => $channelOptions])
            <x-forms.footer :cancel-href="route('marketing.campaigns.show', $campaign)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
