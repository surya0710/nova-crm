# Workflow API Reference

## Endpoint
`/api/workflow/{resource}`

## Method
`GET | POST | PUT | PATCH | DELETE`

## Authentication
Bearer token using NovaCRM API token permissions.

## Request
Document query parameters, payload schema, and supported filters.

## Validation
List required and optional fields plus constraint rules.

## Example Request
`POST /api/workflow/rules`

## Example Response
`{"data":{"id":55,"enabled":true}}`

## Error Codes
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error

## Related Events
Document workflow lifecycle events and trigger side effects.
