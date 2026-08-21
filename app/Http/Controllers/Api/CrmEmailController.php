<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiCrmEmailRequest;
use App\Http\Requests\StoreApiCrmEmailRequest;
use App\Http\Resources\CrmEmailConversationResource;
use App\Http\Resources\CrmEmailMessageResource;
use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Services\CrmEmailMetricsService;
use App\Services\CrmEmailService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrmEmailController extends Controller
{
    public function __construct(
        protected CrmEmailService $emails,
        protected CrmEmailMetricsService $metrics,
        protected TenantContext $tenant,
    ) {}

    public function send(StoreApiCrmEmailRequest $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $related = $this->resolveRelated((string) $request->validated('related_type'), (int) $request->validated('related_id'));
        abort_unless($related, 404);

        try {
            $message = $this->emails->send(
                $organization,
                $request->user(),
                $related,
                $request->validated(),
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return (new CrmEmailMessageResource($message))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode($message->isQueued() ? 202 : 201);
    }

    public function messages(IndexApiCrmEmailRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CrmEmailMessage::class);

        $query = CrmEmailMessage::query()->with(['customer', 'contact', 'sender']);

        if ($search = $request->string('search')->toString()) {
            $like = '%'.$search.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('subject', 'like', $like)
                    ->orWhere('body', 'like', $like);
            });
        }

        foreach (['status', 'customer_id', 'contact_id', 'template_id', 'sent_by'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        return CrmEmailMessageResource::collection(
            $query->latest('id')->paginate($request->perPage())->withQueryString()
        );
    }

    public function showMessage(CrmEmailMessage $message): CrmEmailMessageResource
    {
        $this->authorize('view', $message);
        abort_unless((int) $message->organization_id === (int) ($this->tenant->id() ?? 0), 404);

        return new CrmEmailMessageResource($message->load(['customer', 'contact', 'sender']));
    }

    public function conversations(IndexApiCrmEmailRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CrmEmailConversation::class);

        $query = CrmEmailConversation::query()->with(['customer', 'contact']);

        if ($search = $request->string('search')->toString()) {
            $query->where('subject', 'like', '%'.$search.'%');
        }
        if ($request->filled('status')) {
            $query->where('last_status', $request->string('status')->toString());
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        return CrmEmailConversationResource::collection(
            $query->orderByDesc('last_message_at')->paginate($request->perPage())->withQueryString()
        );
    }

    public function showConversation(CrmEmailConversation $conversation): CrmEmailConversationResource
    {
        $this->authorize('view', $conversation);
        abort_unless((int) $conversation->organization_id === (int) ($this->tenant->id() ?? 0), 404);

        return new CrmEmailConversationResource(
            $conversation->load(['customer', 'contact', 'messages' => fn ($q) => $q->orderBy('id')])
        );
    }

    public function conversationMessages(Request $request, CrmEmailConversation $conversation): AnonymousResourceCollection
    {
        $this->authorize('view', $conversation);
        abort_unless((int) $conversation->organization_id === (int) ($this->tenant->id() ?? 0), 404);

        return CrmEmailMessageResource::collection(
            $conversation->messages()->orderBy('id')->paginate(min(100, max(1, (int) $request->input('per_page', 50))))->withQueryString()
        );
    }

    public function metrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CrmEmailMessage::class);

        return response()->json($this->metrics->summary(
            $this->tenant->get(),
            $request->date('from'),
            $request->date('to'),
        ));
    }

    protected function resolveRelated(string $type, int $id): ?Model
    {
        $class = config('crm_email.related_types.'.$type);
        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        return $class::query()->find($id);
    }
}
