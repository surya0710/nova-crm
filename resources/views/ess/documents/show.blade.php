<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$document->title"
        :subtitle="config('hrms.document_categories.'.$document->category, $document->category)"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Documents'), 'href' => route('ess.documents.index')],
                ['label' => $document->title, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('ess.documents.download', $document)" variant="primary" size="sm">{{ __('Download Current Version') }}</x-ui.button>
        </x-slot:actions>

        @include('ess.partials.nav')

        <x-entity.section :title="__('Document details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Category')">{{ config('hrms.document_categories.'.$document->category, $document->category) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Verification')">{{ config('hrms.document_verification_statuses.'.$document->verification_status, $document->verification_status) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Expires')">{{ $document->expires_at?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Version History')">
            @foreach ($document->versions as $version)
                <div class="flex justify-between items-center text-sm py-2 border-b border-line last:border-0">
                    <span class="text-ink-heading">v{{ $version->version_no }} · {{ $version->original_name }}</span>
                    <x-ui.button :href="route('ess.documents.download', [$document, 'version_id' => $version->id])" variant="ghost" size="sm">{{ __('Download') }}</x-ui.button>
                </div>
            @endforeach
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
