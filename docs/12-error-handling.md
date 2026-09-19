# Error Handling

All API errors render as JSON (never Blade pages), matched on the `api/*`
path so even header-less clients get parseable responses.

Validation errors (`422`):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

Authentication errors (`401` — missing/invalid token):

```json
{
  "message": "Unauthenticated."
}
```

Authorisation errors (`403` — valid token, insufficient rights):

```json
{
  "message": "This action is unauthorized."
}
```

Inactive account (`403` — the `active` middleware):

```json
{
  "message": "Account is inactive."
}
```

Rate limiting (`429` — public auth endpoints, see throttles in
`routes/api.php`):

```json
{
  "message": "Too Many Attempts."
}
```

Unknown routes and report types return `404` with a JSON message.
State-transition violations (e.g. approving a non-pending transfer, or
receiving more than ordered) return `422` with the business reason —
treat the message as user-displayable.
