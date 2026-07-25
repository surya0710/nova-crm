@php
    $density = $shellNav['density'] ?? 'comfortable';
    $versionColumns = [__('Version'), __('File'), __('Uploaded By'), __('Uploaded At'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$document->title"
        :subtitle="$employee->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'href' => route('hrms.employees.show', $employee)],
                ['label' => __('Documents'), 'href' => route('hrms.employees.documents.index', $employee)],
                ['label' => $document->title, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.employees.documents.index', $employee)" variant="secondary" size="sm">{{ __('Back to Documents') }}</x-ui.button>
            @if ($document->currentVersion)
                <x-ui.button :href="route('hrms.employees.documents.download', [$employee, $document])" variant="primary" size="sm">{{ __('Download Current') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $document->verificationStatusLabel() }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Document Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Category')">{{ $document->categoryLabel() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Verification')">{{ $document->verificationStatusLabel() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Verified By')">{{ $document->verifier?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Verified At')">{{ $document->verified_at?->format('Y-m-d H:i') ?? '—' }}</x-entity.definition-item>
                @if ($document->verification_notes)
                    <x-entity.definition-item :label="__('Verification Notes')" :span="2">{{ $document->verification_notes }}</x-entity.definition-item>
                @endif
                <x-entity.definition-item :label="__('Expiry')">
                    @if ($document->expires_at)
                        {{ $document->expires_at->format('Y-m-d') }}
                        @if ($document->isExpired())
                            <span class="text-danger">({{ __('Expired') }})</span>
                        @elseif ($document->isExpiringSoon())
                            <span class="text-warning">({{ __('Expiring soon') }})</span>
                        @endif
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                @if ($document->currentVersion)
                    <x-entity.definition-item :label="__('Current Version')">v{{ $document->currentVersion->version_no }} — {{ $document->currentVersion->original_name }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Uploaded By')">{{ $document->currentVersion->uploader?->name ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Uploaded At')">{{ $document->currentVersion->created_at?->format('Y-m-d H:i') }}</x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Version History')">
            @if ($document->versions->isEmpty())
                <x-ui.empty-state-preset variant="documents" />
            @else
                <x-tables.table :columns="$versionColumns" :dense="$density === 'compact'">
                    @foreach ($document->versions as $version)
                        <tr @class(['hover:bg-surface-muted/60 transition', 'bg-primary-50/50' => $version->id === $document->current_version_id])>
                            <td class="px-4 py-3 text-sm text-ink-heading">
                                v{{ $version->version_no }}
                                @if ($version->id === $document->current_version_id)
                                    <span class="text-xs text-primary-700">({{ __('Current') }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $version->original_name }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $version->uploader?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $version->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 space-x-2">
                                <x-ui.button :href="route('hrms.employees.documents.download', [$employee, $document, 'version' => $version->id])" variant="link" size="sm">{{ __('Download') }}</x-ui.button>
                                @can('manage', $document)
                                    @if ($version->id !== $document->current_version_id)
                                        <form method="POST" action="{{ route('hrms.employees.documents.restore-version', [$employee, $document]) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="version_id" value="{{ $version->id }}">
                                            <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Restore') }}</x-ui.button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-entity.section>

        @can('manage', $document)
            <x-slot:aside>
                <x-entity.section :title="__('Update Metadata')">
                    <form method="POST" action="{{ route('hrms.employees.documents.update', [$employee, $document]) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <x-forms.field name="title">
                            <x-forms.input name="title" :value="old('title', $document->title)" />
                        </x-forms.field>
                        <x-forms.field name="category">
                            <x-forms.select name="category">
                                @foreach (config('hrms.document_categories', []) as $key => $label)
                                    <option value="{{ $key }}" @selected(old('category', $document->category) === $key)>{{ $label }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field name="expires_at">
                            <x-forms.input type="date" name="expires_at" :value="old('expires_at', $document->expires_at?->format('Y-m-d'))" />
                        </x-forms.field>
                        <x-forms.field name="file">
                            <input type="file" name="file" class="block w-full text-sm text-ink-muted file:mr-4 file:rounded-md file:border-0 file:bg-surface-muted file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink-heading hover:file:bg-neutral-100" />
                        </x-forms.field>
                        <textarea name="notes" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2" placeholder="{{ __('Version notes (optional)') }}"></textarea>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Changes') }}</x-ui.button>
                    </form>
                </x-entity.section>

                <x-entity.section :title="__('Verify Document')">
                    <form method="POST" action="{{ route('hrms.employees.documents.verify', [$employee, $document]) }}" class="space-y-3">
                        @csrf
                        <x-forms.field name="verification_status">
                            <x-forms.select name="verification_status" required>
                                <option value="verified">{{ __('Verified') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                            </x-forms.select>
                        </x-forms.field>
                        <textarea name="verification_notes" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2" placeholder="{{ __('Verification notes') }}"></textarea>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Verification') }}</x-ui.button>
                    </form>
                </x-entity.section>

                <x-entity.section :title="__('Delete')">
                    <form method="POST" action="{{ route('hrms.employees.documents.destroy', [$employee, $document]) }}" onsubmit="return confirm('{{ __('Delete this document?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete Document') }}</x-ui.button>
                    </form>
                </x-entity.section>
            </x-slot:aside>
        @endcan
    </x-layouts.entity-detail>
</x-app-layout>
