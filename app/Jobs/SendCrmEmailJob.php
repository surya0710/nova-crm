<?php

namespace App\Jobs;

use App\Models\CrmEmailMessage;
use App\Models\Organization;
use App\Services\CrmEmailConversationService;
use App\Services\CrmEmailDeliveryService;
use App\Services\CrmEmailHeaderApplier;
use App\Services\CrmEmailMailableFactory;
use App\Services\CrmActivityService;
use App\Services\OrganizationMailConfig;
use App\Services\OrganizationMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCrmEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public int $timeout = 120;

    public function __construct(public int $messageId)
    {
        $this->onQueue(config('crm_email.queue.name', 'mail'));
        $connection = config('crm_email.queue.connection');
        if (filled($connection)) {
            $this->onConnection($connection);
        }

        $this->tries = (int) config('crm_email.queue.tries', 3);
        $this->backoff = config('crm_email.queue.backoff', [30, 120]);
        $this->timeout = (int) config('crm_email.queue.timeout', 120);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('crm-email-'.$this->messageId))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(
        OrganizationMailer $mailer,
        OrganizationMailConfig $mailConfig,
        CrmEmailMailableFactory $factory,
        CrmEmailHeaderApplier $headers,
        CrmEmailDeliveryService $delivery,
        CrmEmailConversationService $conversations,
        CrmActivityService $activities,
    ): void {
        $message = CrmEmailMessage::withoutGlobalScopes()->find($this->messageId);

        if (! $message) {
            return;
        }

        if (in_array($message->status, ['sent', 'delivered', 'bounced'], true)) {
            Log::info('crm.email.job_skipped', [
                'message_id' => $message->id,
                'status' => $message->status,
                'reason' => 'already_sent',
            ]);

            return;
        }

        $organization = Organization::query()->find($message->organization_id);
        if (! $organization || ! $mailer->isConfigured($organization)) {
            $delivery->markFailed($message, __('Organization email is not configured.'));

            throw new \RuntimeException(__('Organization email is not configured.'));
        }

        $delivery->markSending($message);
        $message->refresh();

        $config = $mailConfig->for($organization);
        $mailable = $factory->make($message, $organization);
        if (property_exists($mailable, 'emailSignature')) {
            $mailable->emailSignature = $config->signature();
        }

        $headers->apply($mailable, $message);

        try {
            $mailer->send(
                $organization,
                $message->to ?? [],
                $mailable,
                $message->cc ?? [],
                $message->bcc ?? [],
            );
        } catch (Throwable $e) {
            $delivery->markFailed($message, $e->getMessage());
            Log::warning('crm.email.send_failed', [
                'message_id' => $message->id,
                'organization_id' => $message->organization_id,
                'reason' => $e->getMessage(),
            ]);
            throw $e;
        }

        $delivery->markSent($message, $message->rfc_message_id);

        if ($message->conversation) {
            $conversations->refresh($message->conversation);
        }

        $this->logActivity($message, $activities);

        Log::info('crm.email.sent', [
            'message_id' => $message->id,
            'organization_id' => $message->organization_id,
            'provider' => $message->provider,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $message = CrmEmailMessage::withoutGlobalScopes()->find($this->messageId);
        if ($message && ! in_array($message->status, ['sent', 'delivered', 'bounced'], true)) {
            app(CrmEmailDeliveryService::class)->markFailed(
                $message,
                $exception?->getMessage() ?? __('Email queue job failed.'),
            );
        }

        Log::critical('crm.email.job_failed', [
            'message_id' => $this->messageId,
            'reason' => $exception?->getMessage() ?? 'CRM email queue job failed.',
            'exception' => $exception,
        ]);
    }

    protected function logActivity(CrmEmailMessage $message, CrmActivityService $activities): void
    {
        if (! $message->customer || ! $message->sender) {
            return;
        }

        $alreadyLogged = $message->customer->activities()
            ->where('type', 'email')
            ->where('metadata->email_message_id', $message->id)
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        $activities->logEmail(
            $message->customer,
            $message->sender,
            $message->subject,
            (string) $message->body,
            $message->to[0] ?? null,
            $message->contact,
            [
                'to' => $message->to,
                'cc' => $message->cc,
                'bcc' => $message->bcc,
                'email_message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'status' => $message->status,
            ],
        );
    }
}
