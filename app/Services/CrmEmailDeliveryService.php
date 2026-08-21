<?php

namespace App\Services;

use App\Models\CrmEmailMessage;
use Illuminate\Support\Facades\Log;

class CrmEmailDeliveryService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markSending(CrmEmailMessage $message): void
    {
        if (in_array($message->status, ['sent', 'delivered', 'bounced'], true)) {
            return;
        }

        $message->forceFill([
            'status' => 'sending',
            'sending_at' => $message->sending_at ?? now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markSent(CrmEmailMessage $message, ?string $providerMessageId = null, array $metadata = []): void
    {
        if (in_array($message->status, ['delivered', 'bounced'], true)) {
            return;
        }

        $message->forceFill([
            'status' => 'sent',
            'sent_at' => $message->sent_at ?? now(),
            'provider_message_id' => $providerMessageId ?: $message->provider_message_id ?: $message->rfc_message_id,
            'provider_metadata' => $this->mergeMetadata($message, $metadata),
            'error_message' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markDelivered(CrmEmailMessage $message, array $metadata = []): bool
    {
        if (! $message->supportsDeliveryTracking()) {
            Log::info('crm.email.delivery_ignored', [
                'message_id' => $message->id,
                'provider' => $message->provider,
                'reason' => 'provider_does_not_support_tracking',
            ]);

            return false;
        }

        if ($message->status === 'bounced') {
            return false;
        }

        $message->forceFill([
            'status' => 'delivered',
            'delivered_at' => $message->delivered_at ?? now(),
            'sent_at' => $message->sent_at ?? now(),
            'provider_metadata' => $this->mergeMetadata($message, $metadata),
        ])->save();

        return true;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markFailed(CrmEmailMessage $message, string $reason, array $metadata = []): void
    {
        if (in_array($message->status, ['delivered', 'bounced'], true)) {
            return;
        }

        $message->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $reason,
            'provider_metadata' => $this->mergeMetadata($message, $metadata),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markBounced(CrmEmailMessage $message, ?string $type, ?string $reason, array $metadata = []): bool
    {
        if (! $message->supportsDeliveryTracking() && $message->status !== 'sent' && $message->status !== 'delivered') {
            return false;
        }

        if (! $message->supportsDeliveryTracking()) {
            Log::info('crm.email.bounce_ignored', [
                'message_id' => $message->id,
                'provider' => $message->provider,
                'reason' => 'provider_does_not_support_tracking',
            ]);

            return false;
        }

        $message->forceFill([
            'status' => 'bounced',
            'bounced_at' => $message->bounced_at ?? now(),
            'bounce_type' => $type,
            'bounce_reason' => $reason,
            'sent_at' => $message->sent_at ?? now(),
            'provider_metadata' => $this->mergeMetadata($message, $metadata),
        ])->save();

        return true;
    }

    /**
     * Deferred events stay as sent — they are not delivery.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordDeferred(CrmEmailMessage $message, array $metadata = []): void
    {
        $message->forceFill([
            'provider_metadata' => $this->mergeMetadata($message, array_merge($metadata, [
                'deferred_at' => now()->toIso8601String(),
            ])),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function mergeMetadata(CrmEmailMessage $message, array $metadata): array
    {
        if ($metadata === []) {
            return $message->provider_metadata ?? [];
        }

        return array_merge($message->provider_metadata ?? [], $metadata);
    }
}
