# App Version API

Lets the mobile app check whether the version it's running is current, whether an update is required, and whether the app is currently in maintenance mode. Called on launch, before the app does anything else.

This endpoint is exempt from Laravel's maintenance-mode lockout, so it keeps responding (with a maintenance payload) even while the rest of the API is down. It still requires the shared tenant secret — the client bundles that secret regardless of login state, so it's available on every launch.

---

## Endpoint

```
GET /api/v1/app/version
```

---

## Authentication

Requires a shared secret, sent by every caller.

```
X-Tenant-Secret: <shared secret>
```

Also rate limited (`api-public`: 60 requests/minute per IP in production, unthrottled otherwise).

---

## Request

**Content-Type:** N/A (query string)

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| `platform` | string | Yes | One of `ios`, `android` |
| `version` | string | No | Client's current app version (semver). Defaults to `0.0.0` if omitted, which forces `update_required: true` |
| `tenant` | string | No | One of the tenant registry values (e.g. `uk`, `staging`, `local`) |

```
GET /api/v1/app/version?platform=ios&version=1.2.0
```

---

## Behaviour

1. **Maintenance mode check first:** if the app is down for maintenance (`php artisan down`), the endpoint short-circuits and always returns the maintenance payload (503), regardless of the version supplied.
2. **Without `tenant`:** version requirements are read from `tenants.default.mobile.{platform}.{minimum_version,current_version}` config.
3. **With `tenant`:** version requirements are read from `tenants.{tenant}.mobile.{platform}.{minimum_version,current_version}` config instead, falling back to `1.0.0` if unset.
4. `is_current` is true when `version >= latest_version`.
5. `update_required` is true when `version < minimum_version`.
6. `store_url` is read from `mobile.{platform}.store-url` config, hardcoded in the core package (`paul-adams-985/anygoodie-core`'s `config/mobile.php`) as the single source of truth shared by both this app and the tenant apps — not env-driven.

---

## Response

This endpoint returns a JSON:API resource (`data.type`/`id`/`attributes` plus a top-level `jsonapi` block, per `JsonApiResource::configure()` in `AppServiceProvider`). [api-user-exists.md](api-user-exists.md) predates this convention and still returns a flat `data: {...}` object — the two response envelopes are intentionally inconsistent for now.

### 200 OK

```json
{
  "data": {
    "type": "app-version",
    "id": "ios",
    "attributes": {
      "platform": "ios",
      "is_current": true,
      "update_required": false,
      "latest_version": "1.2.0",
      "minimum_version": "1.0.0",
      "store_url": "https://apps.apple.com/app/example"
    }
  },
  "meta": {
    "message": "Success"
  },
  "jsonapi": {
    "version": "1.0"
  }
}
```

### 503 Service Unavailable — maintenance mode

Returned instead of the version payload whenever the app is down for maintenance, taking priority over the version check.

```json
{
  "data": {
    "type": "maintenance",
    "id": "current",
    "attributes": {
      "maintenance": true,
      "message": "The app is currently undergoing maintenance. Please try again later.",
      "retry_after": 60
    }
  },
  "meta": {
    "message": "Success"
  }
}
```

`message` comes from `mobile.maintenance-message` config (hardcoded in the core package); `retry_after` is whatever `retry` value was passed to `php artisan down --retry=60`, or `null` if not set.

### 401 Unauthorized

Missing or incorrect `X-Tenant-Secret` header.

```json
{
  "errors": [
    {
      "status": "401",
      "title": "Invalid tenant secret"
    }
  ]
}
```

### 422 Unprocessable Entity

Standard Laravel validation error shape (not the JSON:API `errors[]` format used above for 401 — that shape is hand-crafted per-error and isn't applied to validation failures).

```json
{
  "message": "Platform must be either ios or android.",
  "errors": {
    "platform": ["Platform must be either ios or android."]
  }
}
```

---

## Examples

### cURL

```bash
curl "https://anygoodie-bridge.test/api/v1/app/version?platform=ios&version=1.2.0" \
  -H "X-Tenant-Secret: <shared secret>"
```

### cURL, targeting a specific tenant

```bash
curl "https://anygoodie-bridge.test/api/v1/app/version?platform=ios&version=1.2.0&tenant=uk" \
  -H "X-Tenant-Secret: <shared secret>"
```

### Swift (iOS)

```swift
var components = URLComponents(string: "https://anygoodie-bridge.test/api/v1/app/version")!
components.queryItems = [
    URLQueryItem(name: "platform", value: "ios"),
    URLQueryItem(name: "version", value: appVersion),
]

var request = URLRequest(url: components.url!)
request.httpMethod = "GET"
request.setValue("<shared secret>", forHTTPHeaderField: "X-Tenant-Secret")
```

### Kotlin (Android)

```kotlin
val url = "https://anygoodie-bridge.test/api/v1/app/version".toHttpUrl().newBuilder()
    .addQueryParameter("platform", "android")
    .addQueryParameter("version", appVersion)
    .build()

val request = Request.Builder()
    .url(url)
    .addHeader("X-Tenant-Secret", sharedSecret)
    .get()
    .build()
```
