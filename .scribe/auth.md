# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Grab a token via <b>POST /api/v1/auth/login</b> (demo: manager@assetarray.test / password). Deactivated accounts get <b>403</b> on every call.
