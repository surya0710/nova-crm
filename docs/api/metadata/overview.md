# Metadata API Reference

## Endpoint
`/api/metadata/{resource}`

## Method
`GET | POST | PUT | PATCH | DELETE`

## Authentication
Bearer token using NovaCRM API token permissions.

## Request
Document query parameters, payload schema, and supported filters.

## Validation
List required and optional fields plus constraint rules.

## Example Request
`POST /api/metadata/fields`

## Example Response
`{"data":{"id":77,"slug":"custom_field"}}`

## Error Codes
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error

## Related Events
Document events published for field lifecycle changes.
