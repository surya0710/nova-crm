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

## Quotations

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/quotations` | view / create |
| GET/PUT | `/api/v1/quotations/{id}` | view / update |
| POST | `/api/v1/quotations/{id}/convert` | `invoices.create` + `quotations.view` |

Create body matches the web form: `customer_id`, `status=draft`, `issue_date`, `currency`, `items[]` (description, quantity, unit_price, optional product_id, sku, hsn_sac, unit, tax_rate, discount, cess, tax_inclusive), plus `terms`, `place_of_supply`, `shipping_amount`. Items are returned on the quotation resource.

Convert copies the tax snapshot onto a draft invoice (201). Repeat convert returns the existing invoice (200).

## Invoices

| Method | Path | Permission |
|--------|------|------------|
| GET/POST | `/api/v1/invoices` | view / create |
| GET/PUT | `/api/v1/invoices/{id}` | view / update |

New invoices must be `draft`. Responses include GST totals, billing/shipping snapshots, due date, terms, and line items.

## Error codes

- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error
