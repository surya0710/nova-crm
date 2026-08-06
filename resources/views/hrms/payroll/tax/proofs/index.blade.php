<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tax Proofs')" :subtitle="__('Upload and verify investment proofs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Proofs'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('upload', \App\Models\TaxProof::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.proofs.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Declaration')" name="tax_declaration_id" required>
                        <x-forms.select name="tax_declaration_id" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($declarations as $declaration)
                                <option value="{{ $declaration->id }}" @selected(old('tax_declaration_id') == $declaration->id)>
                                    {{ $declaration->declaration_number }} — {{ $declaration->employee?->full_name }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Category')" name="category" required>
                        <x-forms.input name="category" :value="old('category')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Title')" name="title" required>
                        <x-forms.input name="title" :value="old('title')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Claimed Amount')" name="claimed_amount" required>
                        <x-forms.input name="claimed_amount" type="number" step="0.01" :value="old('claimed_amount')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('File')" name="file">
                        <x-forms.input name="file" type="file" />
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Upload Proof') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Number'), __('Employee'), __('Title'), __('Claimed'), __('Approved'), __('Status'), __('Actions')]">
                @forelse ($proofs as $proof)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $proof->proof_number }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $proof->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $proof->title }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $proof->claimed_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $proof->approved_amount ? number_format((float) $proof->approved_amount, 2) : '—' }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ $proof->status }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-sm space-x-1">
                            @if (in_array($proof->status, [\App\Models\TaxProof::STATUS_UPLOADED, \App\Models\TaxProof::STATUS_PARTIAL], true))
                                @can('verify', $proof)
                                    <form method="POST" action="{{ route('hrms.payroll.tax.proofs.verify', $proof) }}" class="inline">@csrf
                                        <input type="hidden" name="approved_amount" value="{{ $proof->claimed_amount }}">
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Verify') }}</x-ui.button>
                                    </form>
                                    <form method="POST" action="{{ route('hrms.payroll.tax.proofs.reject', $proof) }}" class="inline">@csrf
                                        <input type="hidden" name="comments" value="{{ __('Rejected from listing') }}">
                                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Reject') }}</x-ui.button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $proofs->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
