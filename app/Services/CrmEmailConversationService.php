<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CrmEmailConversationService
{
    public function assign(CrmEmailMessage $message): CrmEmailConversation
    {
        $threadId = $this->resolveThreadId($message);
        $message->thread_id = $threadId;

        $conversation = CrmEmailConversation::query()
            ->where('organization_id', $message->organization_id)
            ->where('thread_id', $threadId)
            ->first();

        if (! $conversation) {
            $conversation = CrmEmailConversation::query()->create([
                'organization_id' => $message->organization_id,
                'customer_id' => $message->customer_id,
                'contact_id' => $message->contact_id,
                'related_type' => $message->related_type,
                'related_id' => $message->related_id,
                'thread_id' => $threadId,
                'subject' => $message->subject,
                'message_count' => 0,
                'last_status' => $message->status,
                'last_message_at' => $message->queued_at ?? now(),
            ]);
        }

        $message->conversation_id = $conversation->id;
        $message->save();

        $this->refresh($conversation);

        return $conversation->fresh();
    }

    public function refresh(CrmEmailConversation $conversation): void
    {
        $count = $conversation->messages()->count();
        $latest = $conversation->messages()->latest('id')->first();

        $conversation->forceFill([
            'message_count' => $count,
            'last_status' => $latest?->status ?? $conversation->last_status,
            'last_message_at' => $latest?->sent_at
                ?? $latest?->queued_at
                ?? $latest?->created_at
                ?? $conversation->last_message_at,
            'customer_id' => $conversation->customer_id ?: $latest?->customer_id,
            'contact_id' => $conversation->contact_id ?: $latest?->contact_id,
        ])->save();
    }

    /**
     * @return Collection<int, CrmEmailConversation>
     */
    public function forRelated(Model $related, int $limit = 20): Collection
    {
        return $this->queryForRelated($related)
            ->with(['customer', 'contact', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get();
    }

    public function queryForRelated(Model $related): Builder
    {
        $query = CrmEmailConversation::query();

        if ($related instanceof Customer) {
            return $query->where('customer_id', $related->id);
        }

        if ($related instanceof Contact) {
            return $query->where('contact_id', $related->id);
        }

        return $query->where(function (Builder $inner) use ($related) {
            $inner->where(function (Builder $direct) use ($related) {
                $direct->where('related_type', $related->getMorphClass())
                    ->where('related_id', $related->getKey());
            })->orWhereHas('messages', function (Builder $messages) use ($related) {
                $messages->where('related_type', $related->getMorphClass())
                    ->where('related_id', $related->getKey());
            });
        });
    }

    public function generateRfcMessageId(int $organizationId): string
    {
        $host = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

        return sprintf('crm-%s-%s@%s', $organizationId, Str::lower((string) Str::ulid()), $host);
    }

    protected function resolveThreadId(CrmEmailMessage $message): string
    {
        if (filled($message->thread_id)) {
            return (string) $message->thread_id;
        }

        $inReplyTo = $this->normalizeId($message->in_reply_to);
        if ($inReplyTo !== '') {
            $parent = CrmEmailMessage::query()
                ->where('organization_id', $message->organization_id)
                ->where(function (Builder $query) use ($inReplyTo) {
                    $query->where('rfc_message_id', $inReplyTo)
                        ->orWhere('rfc_message_id', '<'.$inReplyTo.'>');
                })
                ->first();

            if ($parent?->thread_id) {
                return $parent->thread_id;
            }
        }

        $references = preg_split('/\s+/', (string) $message->references_header) ?: [];
        foreach ($references as $reference) {
            $id = $this->normalizeId($reference);
            if ($id === '') {
                continue;
            }

            $parent = CrmEmailMessage::query()
                ->where('organization_id', $message->organization_id)
                ->where(function (Builder $query) use ($id) {
                    $query->where('rfc_message_id', $id)
                        ->orWhere('rfc_message_id', '<'.$id.'>');
                })
                ->first();

            if ($parent?->thread_id) {
                return $parent->thread_id;
            }
        }

        return $this->normalizeId($message->rfc_message_id) ?: $this->generateRfcMessageId((int) $message->organization_id);
    }

    protected function normalizeId(?string $value): string
    {
        return trim((string) $value, " \t\n\r\0\x0B<>");
    }
}
