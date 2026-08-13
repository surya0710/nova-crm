<nav class="mb-6 flex flex-wrap gap-3 text-sm border-b border-line pb-3">
    <a href="{{ route('ess.dashboard') }}" class="{{ request()->routeIs('ess.dashboard') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('Dashboard') }}</a>
    <a href="{{ route('ess.profile') }}" class="{{ request()->routeIs('ess.profile*') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('Profile') }}</a>
    <a href="{{ route('ess.documents.index') }}" class="{{ request()->routeIs('ess.documents.*') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('Documents') }}</a>
    <a href="{{ route('ess.attendance.index') }}" class="{{ request()->routeIs('ess.attendance.*') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('attendance.label') }}</a>
    <a href="{{ route('ess.leave.index') }}" class="{{ request()->routeIs('ess.leave.*') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('Leave') }}</a>
    <a href="{{ route('ess.payroll.index') }}" class="{{ request()->routeIs('ess.payroll.*') ? 'font-semibold text-primary-700' : 'text-ink-muted hover:text-ink-heading' }}">{{ __('Payroll') }}</a>
</nav>
