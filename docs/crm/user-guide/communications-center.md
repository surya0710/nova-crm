# Communications Center

**CRM → Communications** (`crm.communications.index`) is the organization inbox for CRM email threads.

- Filter by status and search
- Open a conversation to see message status, recipients, and body
- Permission: `crm_email.view` or `customers.view`
- Other-organization IDs return 404

This is a view over `CrmEmailConversation` / `CrmEmailMessage`. It does not send mail itself — use the record composer or a workflow action.
