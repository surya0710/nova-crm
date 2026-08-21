<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCustomerTicketRequest;
use App\Http\Requests\StoreCustomerTicketNoteRequest;
use App\Http\Requests\StoreCustomerTicketRequest;
use App\Http\Requests\UpdateCustomerTicketRequest;
use App\Http\Resources\CustomerTicketResource;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Services\CustomerTicketService;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerTicketController extends Controller
{
    public function __construct(protected CustomerTicketService $tickets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CustomerTicket::class);

        $query = CustomerTicket::query()->with(['customer', 'contact', 'assignee']);

        if ($customer = $request->route('customer')) {
            $query->where('customer_id', $customer->id);
        }

        $this->tickets->applyIndexFilters($query, $request->all());
        $this->tickets->applyIndexSort($query, $request->all());

        return CustomerTicketResource::collection(
            $query->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(CustomerTicket $ticket): CustomerTicketResource
    {
        $this->authorize('view', $ticket);

        return new CustomerTicketResource($ticket->load(['customer', 'contact', 'assignee', 'notes.user']));
    }

    public function store(StoreCustomerTicketRequest $request, Customer $customer): JsonResponse
    {
        $ticket = $this->tickets->create($customer, $request->validated(), $request->user());

        return (new CustomerTicketResource($ticket->load(['customer', 'contact', 'assignee'])))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCustomerTicketRequest $request, CustomerTicket $ticket): CustomerTicketResource
    {
        $ticket = $this->tickets->update($ticket, $request->validated(), $request->user());

        return new CustomerTicketResource($ticket->load(['customer', 'contact', 'assignee']));
    }

    public function storeNote(StoreCustomerTicketNoteRequest $request, CustomerTicket $ticket): JsonResponse
    {
        $note = $this->tickets->addNote($ticket, $request->validated('body'), $request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $note->id,
                'body' => $note->body,
                'created_at' => $note->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function assign(AssignCustomerTicketRequest $request, CustomerTicket $ticket): CustomerTicketResource
    {
        $ticket = $this->tickets->assign($ticket, $request->validated('assigned_to'), $request->user());

        return new CustomerTicketResource($ticket->load(['customer', 'contact', 'assignee']));
    }

    public function reopen(Request $request, CustomerTicket $ticket): CustomerTicketResource
    {
        $this->authorize('update', $ticket);
        $ticket = $this->tickets->reopen($ticket, $request->user());

        return new CustomerTicketResource($ticket->load(['customer', 'contact', 'assignee']));
    }
}
