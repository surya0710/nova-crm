@php
    $statusColors = [
        'prospect' => 'bg-blue-100 text-blue-800',
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $customer->display_name }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$customer->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $customer->status_label }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">{{ $customer->name }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $customer)
                    <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        {{ __('Edit') }}
                    </a>
                @endcan
                @can('delete', $customer)
                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('{{ __('Delete this customer?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Customer Details') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Contact') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Company') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->company ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->phone ?? '—' }}</dd>
                    </div>
                    @if ($customer->website)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Website') }}</dt>
                            <dd class="mt-1 text-sm"><a href="{{ $customer->website }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">{{ $customer->website }}</a></dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Industry') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->industry ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Account Manager') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $customer->assignee?->name ?? __('Unassigned') }}</dd>
                    </div>
                    @if ($customer->tax_number)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tax Number') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $customer->tax_number }}</dd>
                        </div>
                    @endif
                    @if ($customer->lead)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Converted From Lead') }}</dt>
                            <dd class="mt-1 text-sm"><a href="{{ route('leads.show', $customer->lead) }}" class="text-indigo-600 hover:text-indigo-800">{{ $customer->lead->name }}</a></dd>
                        </div>
                    @endif
                    @if ($customer->tags)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tags') }}</dt>
                            <dd class="mt-2 flex flex-wrap gap-2">
                                @foreach ($customer->tags as $tag)
                                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md">{{ $tag }}</span>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @include('metadata-fields._runtime_detail', [
                'metadataFields' => $metadataFields ?? collect(),
                'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
                'record' => $customer,
            ])

            @if ($statement)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ __('Account Statement') }}</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ __('Read-only financial summary') }}</p>
                        </div>
                        @if (auth()->user()->hasPermission('reports.manage') || auth()->user()->hasPermission('finance.view'))
                            <a href="{{ route('customers.statement.export', $customer) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Export CSV') }}</a>
                        @endif
                    </div>
                    <div class="p-6">
                        @php
                            $stmtCurrency = $statement['currency'];
                            $fmt = fn (float $amount) => number_format($amount, 2).' '.$stmtCurrency;
                        @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <div class="p-4 rounded-lg bg-slate-50">
                                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Total invoiced') }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ $fmt($statement['total_invoiced']) }}</p>
                            </div>
                            <div class="p-4 rounded-lg bg-emerald-50">
                                <p class="text-xs font-semibold uppercase text-emerald-700">{{ __('Total paid') }}</p>
                                <p class="mt-1 text-lg font-bold text-emerald-900">{{ $fmt($statement['total_paid']) }}</p>
                            </div>
                            <div class="p-4 rounded-lg bg-amber-50">
                                <p class="text-xs font-semibold uppercase text-amber-700">{{ __('Balance due') }}</p>
                                <p class="mt-1 text-lg font-bold text-amber-900">{{ $fmt($statement['balance_due']) }}</p>
                            </div>
                        </div>

                        @if ($statement['ledger']->isEmpty())
                            <p class="text-sm text-slate-500 text-center py-6">{{ __('No invoices or payments yet.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                                            <th class="py-2 pr-4">{{ __('Reference') }}</th>
                                            <th class="py-2 pr-4 text-right">{{ __('Debit') }}</th>
                                            <th class="py-2 pr-4 text-right">{{ __('Credit') }}</th>
                                            <th class="py-2 text-right">{{ __('Balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($statement['ledger'] as $entry)
                                            <tr>
                                                <td class="py-2 pr-4 text-slate-700">{{ $entry['date']?->format('M j, Y') ?? '—' }}</td>
                                                <td class="py-2 pr-4">
                                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $entry['type'] === 'invoice' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                        {{ $entry['type'] === 'invoice' ? __('Invoice') : __('Payment') }}
                                                    </span>
                                                </td>
                                                <td class="py-2 pr-4">
                                                    @if ($entry['type'] === 'invoice' && isset($entry['invoice_id']))
                                                        <a href="{{ route('invoices.show', $entry['invoice_id']) }}" class="text-indigo-600 hover:text-indigo-800">{{ $entry['number'] }}</a>
                                                    @elseif ($entry['type'] === 'payment' && isset($entry['payment_id']))
                                                        <a href="{{ route('payments.show', $entry['payment_id']) }}" class="text-indigo-600 hover:text-indigo-800">{{ $entry['number'] }}</a>
                                                    @else
                                                        {{ $entry['number'] }}
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 text-right text-slate-900">{{ $entry['debit'] > 0 ? $fmt($entry['debit']) : '—' }}</td>
                                                <td class="py-2 pr-4 text-right text-emerald-700">{{ $entry['credit'] > 0 ? $fmt($entry['credit']) : '—' }}</td>
                                                <td class="py-2 text-right font-medium text-slate-900">{{ $fmt($entry['balance']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($customer->address_line_1 || $customer->city || $customer->country)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-semibold text-slate-900">{{ __('Address') }}</h3>
                    </div>
                    <div class="p-6 text-sm text-slate-700 leading-relaxed">
                        @if ($customer->address_line_1){{ $customer->address_line_1 }}<br>@endif
                        @if ($customer->address_line_2){{ $customer->address_line_2 }}<br>@endif
                        @if ($customer->city || $customer->state || $customer->postal_code)
                            {{ collect([$customer->city, $customer->state, $customer->postal_code])->filter()->join(', ') }}<br>
                        @endif
                        @if ($customer->country){{ $customer->country }}@endif
                    </div>
                </div>
            @endif

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Activity') }}</h3>
                </div>
                <div class="p-6">
                    @can('update', $customer)
                        <form method="POST" action="{{ route('customers.notes.store', $customer) }}" class="mb-6">
                            @csrf
                            <x-input-label for="body" :value="__('Add a note')" />
                            <textarea id="body" name="body" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                            <div class="mt-3 flex justify-end">
                                <x-primary-button type="submit">{{ __('Add Note') }}</x-primary-button>
                            </div>
                        </form>
                    @endcan

                    @if ($customer->notes->isEmpty())
                        <p class="text-sm text-slate-500 text-center py-6">{{ __('No activity yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($customer->notes as $note)
                                <div class="flex gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($note->user->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <span class="text-sm font-medium text-slate-900">{{ $note->user->name }}</span>
                                            <span class="text-xs text-slate-400">{{ $note->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-wrap">{{ $note->body }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <x-attachments-panel
                attachable-type="customer"
                :attachable-id="$customer->id"
                :attachments="$customer->attachments"
                :can-upload="auth()->user()->can('attachments.create')"
                :can-delete="auth()->user()->can('attachments.delete')"
            />

            <x-tasks-panel
                taskable-type="customer"
                :taskable-id="$customer->id"
                :tasks="$customer->tasks"
                :can-create="auth()->user()->can('tasks.create')"
            />

            @can('update', $customer)
                <x-client-email-form
                    :action="route('customers.send', $customer)"
                    :email="old('email', $customer->email)"
                    :submit-label="__('Send Email')"
                    :title="__('Email Client')"
                    :description="__('Send a message to this customer with optional file attachments')"
                    :organization="$organization"
                    :show-subject="true"
                    :subject="old('subject', __('Message from :name', ['name' => $organization?->name ?? config('app.name')]))"
                    :missing-email-hint="! $customer->email"
                />
            @endcan
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Timeline') }}</h3>
                <dl class="mt-4 space-y-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Created') }}</dt>
                        <dd class="text-sm text-slate-900">{{ $customer->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Last updated') }}</dt>
                        <dd class="text-sm text-slate-900">{{ $customer->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @if ($customer->creator)
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Created by') }}</dt>
                            <dd class="text-sm text-slate-900">{{ $customer->creator->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                ← {{ __('Back to customers') }}
            </a>
        </div>
    </div>
</x-app-layout>
