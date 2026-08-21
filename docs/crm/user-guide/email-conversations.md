# Conversations

Threads are `crm_email_conversations` rows, not a second timeline. `CrmEmailConversationService` groups messages by In-Reply-To / References / `thread_id`.

Conversation panels appear on customer, contact, opportunity, and ticket show pages. The Communications Center lists every thread the user may view (`crm_email.view` or `customers.view`).

Activities still go through `CrmActivityService::logEmail()` on the existing timeline.
