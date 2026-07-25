<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformUser;
use App\Services\Platform\PlatformSupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(PlatformSupportService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.view');

        return view('platform.support.index', [
            'overview' => $service->overview(),
        ]);
    }

    public function tickets(Request $request, PlatformSupportService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.view');

        return view('platform.support.tickets', [
            'tickets' => $service->tickets($request->only(['search', 'status', 'priority'])),
            'filters' => $request->only(['search', 'status', 'priority']),
        ]);
    }

    public function createTicket(): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.manage');

        return view('platform.support.create-ticket', [
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'assignees' => PlatformUser::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeTicket(Request $request, PlatformSupportService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.manage');

        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['nullable', 'string', 'max:100'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['nullable', 'email', 'max:255'],
        ]);

        $ticket = $service->createTicket($validated, auth('platform')->user());

        return redirect()
            ->route('platform.support.tickets.show', $ticket)
            ->with('status', __('Support ticket created.'));
    }

    public function showTicket(PlatformSupportTicket $ticket): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.view');

        $ticket->load(['organization:id,name', 'assignee:id,name', 'creator:id,name']);

        return view('platform.support.show-ticket', [
            'ticket' => $ticket,
            'assignees' => PlatformUser::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateTicket(Request $request, PlatformSupportTicket $ticket, PlatformSupportService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.manage');

        $validated = $request->validate([
            'assignee_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $service->updateTicket($ticket, $validated, auth('platform')->user());

        return back()->with('status', __('Support ticket updated.'));
    }

    public function announcements(Request $request, PlatformSupportService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.view');

        return view('platform.support.announcements', [
            'announcements' => $service->announcements($request->only(['type', 'status'])),
            'filters' => $request->only(['type', 'status']),
        ]);
    }

    public function storeAnnouncement(Request $request, PlatformSupportService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.support.manage');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['nullable', Rule::in(['announcement', 'maintenance', 'incident'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'broadcast' => ['nullable', 'boolean'],
        ]);

        $service->createAnnouncement($validated, auth('platform')->user());

        return redirect()
            ->route('platform.support.announcements')
            ->with('status', __('Announcement created.'));
    }
}
