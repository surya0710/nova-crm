# CRM API Overview

Commercial endpoints live under `/api/v1` with Sanctum authentication, `X-Organization-Id`, `api.access`, and the entity permission.

## Authentication and isolation

- `Authorization: Bearer {token}`
- `X-Organization-Id: {id}`
- Missing permission → `403`
- Other-organization IDs → `404`

## Products

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/products` | `products.view` |
| POST | `/api/v1/products` | `products.create` |
| GET | `/api/v1/products/{id}` | `products.view` |
| PUT/PATCH | `/api/v1/products/{id}` | `products.update` |
| DELETE | `/api/v1/products/{id}` | `products.delete` |

Query: `search`, `status`, `type`, `product_category_id`, `per_page`.

## Product categories

`/api/v1/product-categories` — standard `apiResource`. Same `products.*` permissions.

## Customer tax profile

Customer GET/PUT already returns GST fields: `gstin`, `pan`, `gst_registration_type`, `billing_state_code`, `place_of_supply`, exemption, shipping address, `default_tax_preference`.

Lifecycle fields: `type`, `lifecycle_stage`, `segment`, `source`, plus `status`, `assigned_to`, and `tags`.

## Contacts

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/contacts` | `customers.view` |
| GET | `/api/v1/customers/{id}/contacts` | `customers.view` |
| POST | `/api/v1/customers/{id}/contacts` | `customers.update` or `customers.create` |
| GET/PUT | `/api/v1/contacts/{id}` | view / update |
| DELETE | `/api/v1/contacts/{id}` | `customers.update` or `customers.delete` |

Fields: `name`, `title`, `department`, `email`, `phone`, `whatsapp`, `is_primary`, `is_decision_maker`, `status`.

## Tickets

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/tickets` | `customers.view` |
| POST | `/api/v1/customers/{id}/tickets` | `customers.update` |
| GET/PUT | `/api/v1/tickets/{id}` | view / update |
| POST | `/api/v1/tickets/{id}/notes` | `customers.update` |
| PATCH | `/api/v1/tickets/{id}/assign` | `customers.update` |
| POST | `/api/v1/tickets/{id}/reopen` | `customers.update` |

Query: `search`, `status`, `priority`, `customer_id`, `contact_id`, `assigned_to`, `overdue`, `unassigned`, `sort`, `sort_direction`. Response includes `due_at`, `sla_hours`, `is_overdue`.

## Opportunities

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/opportunities` | `opportunities.view` |
| POST | `/api/v1/opportunities` | `opportunities.create` |
| GET/PUT | `/api/v1/opportunities/{id}` | view / update |
| PATCH | `/api/v1/opportunities/{id}/stage` | `opportunities.update` |
| POST | `/api/v1/opportunities/{id}/activities` | `opportunities.update` or `customers.update` |

Fields include amount, probability, expected close, source, competitor, weighted amount, next activity, won/lost.

## Sales activities

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/activities` | `customers.view` |
| POST | `/api/v1/activities` | `customers.update` or `customers.create` |
| POST | `/api/v1/activities/{id}/complete` | `customers.update` |
| GET/POST | `/api/v1/contacts/{id}/activities` | nested contact activities |

Query: `scope` (`mine`, `upcoming`, `overdue`, `completed`, `open`, `all`), `type`, `status`, `priority`, `assigned_to`, related ids.

## Sales forecast

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/sales/forecast` | `opportunities.view` |

Optional `year`, `month`. Returns pipeline, weighted pipeline, win rate, averages, by-stage and by-salesperson totals, monthly forecast, and target achievement.

## Quotations

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/quotations` | view / create |
| GET/PUT | `/api/v1/quotations/{id}` | view / update |
| POST | `/api/v1/quotations/{id}/convert` | `sales_orders.create` + `quotations.view` |

Create body matches the web form: `customer_id`, `status=draft`, `issue_date`, `currency`, `items[]` (description, quantity, unit_price, optional product_id, sku, hsn_sac, unit, tax_rate, discount, cess, tax_inclusive), plus `terms`, `place_of_supply`, `shipping_amount`. Items are returned on the quotation resource.

Convert copies the tax snapshot onto a draft sales order (201). Repeat convert returns the existing sales order (200).

## Sales orders

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/sales-orders` | view / create |
| GET/PUT | `/api/v1/sales-orders/{id}` | view / update |
| GET | `/api/v1/sales-orders/{id}/items` | view |
| POST | `/api/v1/sales-orders/{id}/convert` | `invoices.create` + `sales_orders.view` |

Convert copies the order (and quotation reference) onto a draft invoice (201). Repeat convert returns the existing non-cancelled invoice (200).

## Invoices

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/invoices` | view / create |
| GET/PUT | `/api/v1/invoices/{id}` | view / update |
| POST | `/api/v1/invoices/{id}/payments` | `payments.create` |

New invoices must be `draft`. Responses include GST totals, billing/shipping snapshots, due date, terms, and line items. Nested payments allocate a recorded payment to that invoice.

## Payments

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/payments` | `payments.view` / `payments.create` |
| GET | `/api/v1/payments/{id}` | view |

POST records a payment and allocates it to `invoice_id`. Query: `search`, `method`, `invoice_id`, `customer_id`, `per_page`.

## Receivables and ledger

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/receivables` | `invoices.view` or `finance.view` |
| GET | `/api/v1/customers/{id}/ledger` | `customers.view` plus `invoices.view` or `finance.view` |

Receivables return outstanding invoices plus aging and collection metrics. Ledger returns the customer statement (invoiced, paid, credits, debits, running balance) without mutating stored invoice totals.

## Adjustment notes (credit / debit)

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/adjustment-notes` | `adjustment_notes.view` / `create` |
| GET/PUT | `/api/v1/adjustment-notes/{id}` | view / update |
| POST | `/api/v1/adjustment-notes/{id}/apply` | `adjustment_notes.update` |

Aliases `/api/v1/credit-notes` and `/api/v1/debit-notes` reuse the same controller and force `type`. POST body includes `customer_id`, optional `invoice_id`, `status=draft`, `issue_date`, `currency`, `reason`, and `items[]`. Apply does not mutate stored invoice `total` or `amount_paid`.

## Price lists

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/price-lists` | `price_lists.view` / `create` |
| GET/PUT | `/api/v1/price-lists/{id}` | view / update |
| DELETE | `/api/v1/price-lists/{id}` | delete (not allowed on the default list) |

## Customer portal billing

Portal commercial JSON lives under `/api/v1/portal/{organization-slug}` with `auth:client` (not Sanctum). Linked customers may list/show quotations, sales orders, invoices, payments, notes, and ledger. Accept/reject sent quotations and pay outstanding invoices when a gateway is configured. Isolation is `organization_id` + linked `customer_id`.

## CRM email

See [email.md](email.md) for send, templates, history, conversations, delivery status, and metrics. Never returned: SMTP passwords, provider credentials, encryption keys, webhook secrets, or private attachment paths.

## Error codes

- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error
