<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreCrmActivityRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\CrmActivityResource;
use App\Models\Contact;
use App\Models\Customer;
use App\Services\CommercialTimelineService;
use App\Services\ContactService;
use App\Services\CrmActivityService;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contacts,
        protected CrmActivityService $activities,
        protected CommercialTimelineService $timeline,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()->with('customer');

        if ($search = $request->string('search')->toString()) {
            $like = '%'.mb_strtolower($search).'%';
            $query->where(function ($inner) use ($like) {
                $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(phone) LIKE ?', [$like]);
            });
        }

        if ($customer = $request->route('customer')) {
            $query->where('customer_id', $customer->id);
        } elseif ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ContactResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(Contact $contact): ContactResource
    {
        $this->authorize('view', $contact);

        return new ContactResource($contact->load('customer'));
    }

    public function store(StoreContactRequest $request, Customer $customer): JsonResponse
    {
        $contact = $this->contacts->create($customer, $request->validated(), $request->user());

        return (new ContactResource($contact->load('customer')))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $contact = $this->contacts->update($contact, $request->validated());

        return new ContactResource($contact->load('customer'));
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);
        $contact->delete();

        return response()->json(['success' => true]);
    }

    public function activities(Contact $contact): AnonymousResourceCollection
    {
        $this->authorize('view', $contact);

        return CrmActivityResource::collection(
            $contact->activities()->with(['creator', 'assignee'])->paginate(ApiQuery::perPage(request()))->withQueryString()
        );
    }

    public function storeActivity(StoreCrmActivityRequest $request, Contact $contact): JsonResponse
    {
        $activity = $this->activities->createForContact($contact, $request->validated(), $request->user());

        return (new CrmActivityResource($activity))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function timeline(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        return response()->json([
            'data' => $this->timeline->forContact($contact)->map(fn (array $item) => [
                'type' => $item['type'],
                'event' => $item['event'],
                'label' => $item['label'],
                'body' => $item['body'],
                'actor' => $item['actor'],
                'timestamp' => $item['timestamp']?->toIso8601String(),
                'href' => $item['href'],
            ])->values(),
        ]);
    }
}
