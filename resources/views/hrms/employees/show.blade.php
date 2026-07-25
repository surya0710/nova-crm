<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$employee->full_name"
        :subtitle="$employee->employee_code"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.employees.documents.index', $employee)" variant="secondary" size="sm">{{ __('Documents') }}</x-ui.button>
            @if (\Illuminate\Support\Facades\Route::has('hrms.employees.timeline'))
                <x-ui.button :href="route('hrms.employees.timeline', $employee)" variant="secondary" size="sm">{{ __('Timeline') }}</x-ui.button>
            @endif
            <x-ui.button :href="route('hrms.employees.edit', $employee)" variant="primary" size="sm">{{ __('Edit') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="neutral">{{ config('hrms.employment_statuses.'.$employee->status, $employee->status) }}</x-ui.badge>
                @if ($employee->department)
                    <span class="text-xs text-ink-muted">{{ $employee->department->name }}</span>
                @endif
            </div>
        </x-slot:tabs>

        <x-entity.section :title="__('Personal details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Email')">{{ $employee->email ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Mobile')">{{ $employee->mobile ?? $employee->phone ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Date of birth')">{{ $employee->date_of_birth?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Gender')">{{ $employee->gender ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Employment details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employment type')">{{ config('hrms.employment_types.'.$employee->employment_type, $employee->employment_type) ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ config('hrms.employment_statuses.'.$employee->status, $employee->status) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Joining date')">{{ $employee->joining_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Exit date')">{{ $employee->exit_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Organization assignment')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Department')">{{ $employee->department?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Designation')">{{ $employee->designation?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Branch')">{{ $employee->branch?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Manager')">{{ $employee->reportingManager?->full_name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Linked user')" :span="2">{{ $employee->user?->email ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Login & portal access')">
            @if ($employee->user)
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Account status')">{{ $employee->user->displayAccountStatusLabel() }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Portal access')">{{ $employee->user->portal_access_enabled ? __('Enabled') : __('Disabled') }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Last login')">{{ $loginActivity['last_login_at'] ? \Illuminate\Support\Carbon::parse($loginActivity['last_login_at'])->format('M j, Y g:i A') : '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Last logout')">{{ $loginActivity['last_logout_at'] ? \Illuminate\Support\Carbon::parse($loginActivity['last_logout_at'])->format('M j, Y g:i A') : '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Login count')">{{ $loginActivity['login_count'] ?? 0 }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Failed attempts')">{{ $loginActivity['failed_login_attempts'] ?? 0 }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Last password change')">{{ $loginActivity['password_changed_at'] ? \Illuminate\Support\Carbon::parse($loginActivity['password_changed_at'])->format('M j, Y g:i A') : '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Invitation')">
                        @if (($invitationStatus['invitation']['accepted_at'] ?? null))
                            {{ __('Accepted') }}
                        @elseif (($invitationStatus['invitation']['is_expired'] ?? false))
                            {{ __('Expired') }}
                        @elseif (($invitationStatus['invitation']['is_pending'] ?? false))
                            {{ __('Pending') }}
                            @if ($invitationStatus['invitation']['expires_at'] ?? null)
                                <span class="text-ink-muted text-xs">({{ __('expires') }} {{ \Illuminate\Support\Carbon::parse($invitationStatus['invitation']['expires_at'])->format('M j, Y') }})</span>
                            @endif
                        @else
                            —
                        @endif
                    </x-entity.definition-item>
                </x-entity.definition-list>

                @if (auth()->user()->hasPermission('hrms.manage'))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($employee->user->displayAccountStatus() !== 'active' || ($invitationStatus['invitation']['is_pending'] ?? false) || ($invitationStatus['invitation']['is_expired'] ?? false))
                            <form method="POST" action="{{ route('hrms.employees.resend-invitation', $employee) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Resend Invitation') }}</x-ui.button>
                            </form>
                        @endif
                        @if ($employee->user->portal_access_enabled)
                            <form method="POST" action="{{ route('hrms.employees.portal.disable', $employee) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Disable Portal Access') }}</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('hrms.employees.portal.enable', $employee) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Enable Portal Access') }}</x-ui.button>
                            </form>
                        @endif
                        @if ($employee->user->account_status?->value === 'locked')
                            <form method="POST" action="{{ route('hrms.employees.account.unlock', $employee) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Unlock Account') }}</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('hrms.employees.account.lock', $employee) }}">
                                @csrf
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Lock Account') }}</x-ui.button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('hrms.employees.password-reset', $employee) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Send Password Reset') }}</x-ui.button>
                        </form>
                    </div>
                @endif
            @else
                <p class="text-sm text-ink-muted mb-3">{{ __('This employee does not have a login account yet.') }}</p>
                @if (auth()->user()->hasPermission('hrms.manage'))
                    <form method="POST" action="{{ route('hrms.employees.link-user', $employee) }}" class="space-y-3" x-data="{ createLogin: true }">
                        @csrf
                        <input type="hidden" name="create_user" value="1">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <x-forms.field :label="__('Name')" name="name">
                                <x-forms.input name="name" :value="old('name', $employee->full_name)" required />
                            </x-forms.field>
                            <x-forms.field :label="__('Work email')" name="email">
                                <x-forms.input type="email" name="email" :value="old('email', $employee->email)" required />
                            </x-forms.field>
                            <x-forms.field :label="__('Role')" name="role">
                                <x-forms.select name="role">
                                    @foreach (collect(config('rbac.roles', []))->except('organization-owner') as $slug => $role)
                                        <option value="{{ $slug }}" @selected(old('role', config('identity.default_employee_role', 'employee')) === $slug)>
                                            {{ $role['name'] ?? $slug }}
                                        </option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="send_invitation" value="1" checked class="rounded border-line text-primary-600">
                            {{ __('Send Invitation') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="portal_access" value="1" checked class="rounded border-line text-primary-600">
                            {{ __('Portal Access') }}
                        </label>
                        <div>
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Login Account') }}</x-ui.button>
                        </div>
                    </form>
                @endif
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Emergency contacts')">
            @forelse ($employee->emergencyContacts as $contact)
                <div class="flex flex-wrap items-baseline justify-between gap-2 py-2 border-b border-line last:border-0 text-sm">
                    <div>
                        <p class="font-medium text-ink-heading">{{ $contact->name }}</p>
                        <p class="text-xs text-ink-muted">{{ $contact->relationship ?? $contact->relation ?? '—' }}</p>
                    </div>
                    <p class="text-ink-muted">{{ $contact->phone ?? $contact->mobile ?? '—' }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted py-2">{{ __('No emergency contacts on file.') }}</p>
            @endforelse
        </x-entity.section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-entity.section :title="__('Documents')" :subtitle="__(':count on file', ['count' => $documentCount ?? $employee->documents->count()])">
                @forelse ($employee->documents as $document)
                    <a href="{{ route('hrms.employees.documents.show', [$employee, $document]) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $document->title ?? $document->name ?? __('Document') }}</span>
                        <span class="text-xs text-ink-muted shrink-0">{{ $document->created_at?->format('M j, Y') }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="documents" class="!py-4" />
                @endforelse
                <div class="pt-3">
                    <x-ui.button :href="route('hrms.employees.documents.index', $employee)" variant="secondary" size="sm">{{ __('View all documents') }}</x-ui.button>
                </div>
            </x-entity.section>

            <x-entity.section :title="__('Assigned assets')" :subtitle="__(':count assigned', ['count' => $assetCount ?? $employee->assets->count()])">
                @forelse ($employee->assets as $asset)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink-heading truncate">{{ $asset->name ?? $asset->asset_code ?? __('Asset') }}</p>
                            <p class="text-xs text-ink-muted truncate">{{ $asset->status ?? '—' }}</p>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="assets" class="!py-4" />
                @endforelse
                @if (\Illuminate\Support\Facades\Route::has('hrms.assets.index'))
                    <div class="pt-3">
                        <x-ui.button :href="route('hrms.assets.index')" variant="secondary" size="sm">{{ __('Open assets') }}</x-ui.button>
                    </div>
                @endif
            </x-entity.section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-entity.section :title="__('Attendance history')">
                @forelse ($employee->attendanceRecords as $record)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <span class="font-medium text-ink-heading">{{ $record->attendance_date?->format('M j, Y') }}</span>
                        <x-ui.badge variant="neutral">{{ $record->status }}</x-ui.badge>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="attendance" class="!py-4" />
                @endforelse
                @if (\Illuminate\Support\Facades\Route::has('hrms.attendance.index'))
                    <div class="pt-3">
                        <x-ui.button :href="route('hrms.attendance.index', ['employee_id' => $employee->id])" variant="secondary" size="sm">{{ __('View attendance') }}</x-ui.button>
                    </div>
                @endif
            </x-entity.section>

            <x-entity.section :title="__('Leave history')">
                @forelse ($employee->leaveApplications as $leave)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink-heading truncate">{{ $leave->leaveType?->name ?? __('Leave') }}</p>
                            <p class="text-xs text-ink-muted">{{ $leave->start_date?->format('M j') }} – {{ $leave->end_date?->format('M j, Y') }}</p>
                        </div>
                        <x-ui.badge variant="neutral">{{ $leave->status }}</x-ui.badge>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="leave" class="!py-4" />
                @endforelse
                @if (\Illuminate\Support\Facades\Route::has('hrms.leave-applications.index'))
                    <div class="pt-3">
                        <x-ui.button :href="route('hrms.leave-applications.index', ['employee_id' => $employee->id])" variant="secondary" size="sm">{{ __('View leave') }}</x-ui.button>
                    </div>
                @endif
            </x-entity.section>
        </div>

        <x-entity.section :title="__('Related modules')" :subtitle="__('Payroll, performance, timeline, and activity')">
            <div class="flex flex-wrap gap-2">
                @if (\Illuminate\Support\Facades\Route::has('hrms.payroll.index'))
                    <x-ui.button :href="route('hrms.payroll.index')" variant="secondary" size="sm">{{ __('Payroll') }}</x-ui.button>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('hrms.performance.index'))
                    <x-ui.button :href="route('hrms.performance.index')" variant="secondary" size="sm">{{ __('Performance') }}</x-ui.button>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('hrms.employees.timeline'))
                    <x-ui.button :href="route('hrms.employees.timeline', $employee)" variant="secondary" size="sm">{{ __('Timeline & activity') }}</x-ui.button>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('hrms.employees.documents.index'))
                    <x-ui.button :href="route('hrms.employees.documents.index', $employee)" variant="secondary" size="sm">{{ __('Notes & attachments') }}</x-ui.button>
                @endif
            </div>
        </x-entity.section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-entity.section :title="__('Exit employee')">
                <form method="POST" action="{{ route('hrms.employees.exit', $employee) }}" class="space-y-3">
                    @csrf
                    <x-forms.field :label="__('Status')" name="status">
                        <x-forms.select name="status">
                            <option value="resigned">{{ __('Resigned') }}</option>
                            <option value="terminated">{{ __('Terminated') }}</option>
                            <option value="retired">{{ __('Retired') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Exit date')" name="exit_date">
                        <x-forms.input type="date" name="exit_date" />
                    </x-forms.field>
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Mark Exit') }}</x-ui.button>
                </form>
            </x-entity.section>

            <x-entity.section :title="__('Link existing user')">
                @if (! $employee->user_id)
                    <form method="POST" action="{{ route('hrms.employees.link-user', $employee) }}" class="space-y-3">
                        @csrf
                        <x-forms.field :label="__('User')" name="user_id">
                            <x-forms.select name="user_id">
                                <option value="">{{ __('Select User') }}</option>
                                @foreach ($organizationUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Link User') }}</x-ui.button>
                    </form>
                @else
                    <form method="POST" action="{{ route('hrms.employees.unlink-user', $employee) }}" class="mt-1">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Unlink User') }}</x-ui.button>
                    </form>
                @endif
            </x-entity.section>
        </div>
    </x-layouts.entity-detail>
</x-app-layout>
