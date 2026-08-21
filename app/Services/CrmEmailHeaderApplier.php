<?php

namespace App\Services;

use App\Models\CrmEmailMessage;
use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

class CrmEmailHeaderApplier
{
    public function apply(Mailable $mailable, CrmEmailMessage $message): void
    {
        $rfcId = trim((string) $message->rfc_message_id, '<>');
        $inReplyTo = trim((string) $message->in_reply_to, '<>');
        $references = (string) $message->references_header;
        $provider = (string) $message->provider;

        $mailable->withSymfonyMessage(function (Email $email) use ($message, $rfcId, $inReplyTo, $references, $provider) {
            $headers = $email->getHeaders();

            if ($rfcId !== '') {
                if ($headers->has('Message-ID')) {
                    $headers->remove('Message-ID');
                }
                $headers->addIdHeader('Message-ID', $rfcId);
            }

            if ($inReplyTo !== '') {
                $headers->addIdHeader('In-Reply-To', $inReplyTo);
            }

            if ($references !== '') {
                $headers->addTextHeader('References', $references);
            }

            $headers->addTextHeader('X-Konnect-Email-Id', (string) $message->id);
            $headers->addTextHeader('X-Konnect-Organization-Id', (string) $message->organization_id);

            $variables = [
                'konnect_email_id' => (string) $message->id,
                'konnect_org_id' => (string) $message->organization_id,
            ];

            if ($provider === 'sendgrid') {
                $headers->addTextHeader('X-SMTPAPI', json_encode(['unique_args' => $variables]));
            }

            if ($provider === 'mailgun') {
                $headers->addTextHeader('X-Mailgun-Variables', json_encode($variables));
            }
        });
    }
}
