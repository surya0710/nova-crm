<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmCommunicationsController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorizeView();

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $conversations = CrmEmailConversation::query()
            ->with(['customer', 'contact'])
            ->when($status !== '', fn ($query) => $query->where('last_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('subject', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%')
                            ->orWhere('company', 'like', '%'.$search.'%'))
                        ->orWhereHas('contact', fn ($contact) => $contact->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('last_message_at')
            ->paginate(25)
            ->withQueryString();

        return view('crm.communications.index', [
            'conversations' => $conversations,
            'statuses' => config('crm_email.statuses', []),
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
        ]);
    }

    public function show(CrmEmailConversation $conversation, TenantContext $tenant): View
    {
        $this->authorizeView();

        $organization = $tenant->get();
        abort_unless($organization, 404);
        abort_unless((int) $conversation->organization_id === (int) $organization->id, 404);

        $conversation->load(['customer', 'contact']);

        $messages = CrmEmailMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender', 'contact', 'customer'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('crm.communications.show', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    protected function authorizeView(): void
    {
        $user = request()->user();
        abort_unless(
            $user
            && (
                $user->hasPermission('crm_email.view')
                || $user->hasPermission('customers.view')
            ),
            403
        );
    }
}
