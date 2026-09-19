# Authentication Flow

AssetArray API uses Laravel Sanctum for token-based API authentication.

## Login

```http
POST /api/v1/auth/login
```

Request:

```json
{
  "email": "manager@assetarray.test",
  "password": "password"
}
```

Response:

```json
{
  "message": "Login successful.",
  "data": {
    "user": {},
    "token": "...",
    "token_type": "Bearer"
  }
}
```

Failures are deliberately generic (`The provided credentials are incorrect.`)
so responses never reveal which emails exist. Logins are throttled
(10 attempts per minute) — further attempts return `429`.

## Use Token

```http
Authorization: Bearer YOUR_TOKEN
```

## Logout

```http
POST /api/v1/auth/logout
```

Revokes only the token used for this request. Other devices stay signed in.

## Refresh Token

```http
POST /api/v1/auth/refresh
```

Deletes the current token and issues a new one. Discard the old token —
it stops working immediately.

## Current Profile

```http
GET /api/v1/auth/profile
PUT /api/v1/auth/profile
```

Read or partially update your own profile (name, email, password).
The password is assigned plain-text; the model hashes it exactly once.

## Forgot / Reset Password

```http
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
```

`forgot-password` sends a reset link via the configured broker.
`reset-password` consumes the token with `email`, `password` and
`password_confirmation`. Both are throttled like login.
