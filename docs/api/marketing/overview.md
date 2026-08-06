# Marketing API Reference

## Endpoint
`/api/marketing/{resource}`

## Method
`GET | POST | PUT | PATCH | DELETE`

## Authentication
Bearer token using NovaCRM API token permissions.

## Request
Document query parameters, payload schema, and supported filters.

## Validation
List required and optional fields plus constraint rules.

## Example Request
`POST /api/marketing/campaigns`

## Example Response
`{"data":{"id":101,"status":"created"}}`

## Error Codes
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error

## Related Events
Document events emitted after create/update/delete actions.
