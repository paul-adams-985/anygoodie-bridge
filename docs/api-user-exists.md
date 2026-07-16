# User Exists API

Check whether an email already has a recipient account, and on which tenant. Used by the app *before* calling a tenant's `/api/v1/recipient/login`, so the client can decide whether to log in against an existing tenant or ask the user where to create their account.

Every tenant keeps its own isolated user database, so the bridge is the single place clients ask this question — it fans the search out across every tenant that hosts users, aggregates the result, and returns it.

---

## Endpoint

```
POST /api/v1/recipient/user-exists
```

---

## Authentication

Requires a shared secret, sent by every caller.

```
X-Tenant-Secret: <shared secret>
```

---

## Request

**Content-Type:** `application/json`

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| `email` | string | Yes | Valid email, max 255 chars |
| `tenant` | string | No | One of the tenant registry values (e.g. `uk`, `staging`, `local`) |

```json
{
  "email": "someone@example.com"
}
```

---

## Behaviour

1. **Without `tenant`:** search every joinable tenant that hosts users, stopping at the first match — each tenant checks only its own `users` table (see that tenant's own `docs/api-user-exists.md` for its internal contract). Isolated tenants (e.g. Staging/Local) are skipped — they're self-contained and never included in the fan-out.
2. **With `tenant`:** check only the named tenant directly, bypassing isolation. This is how a client pointed at an isolated tenant (e.g. a staging build) reaches that tenant's own account directly, since the fan-out would otherwise never search it.
3. If no tenant has the email, respond with `exists: false`.

---

## Response

### 200 OK — found

```json
{
  "data": {
    "exists": true,
    "tenant": "uk"
  },
  "meta": {
    "message": "User found"
  }
}
```

### 200 OK — not found

```json
{
  "data": {
    "exists": false,
    "tenant": null
  },
  "meta": {
    "message": "User not found"
  }
}
```

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

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation failed",
      "detail": {
        "email": ["Please provide a valid email address."]
      }
    }
  ]
}
```

---

## Examples

### cURL

```bash
curl -X POST https://anygoodie-bridge.test/api/v1/recipient/user-exists \
  -H "X-Tenant-Secret: <shared secret>" \
  -H "Content-Type: application/json" \
  -d '{"email": "someone@example.com"}'
```

### cURL, targeting a specific tenant (e.g. a staging build)

```bash
curl -X POST https://anygoodie-bridge.test/api/v1/recipient/user-exists \
  -H "X-Tenant-Secret: <shared secret>" \
  -H "Content-Type: application/json" \
  -d '{"email": "someone@example.com", "tenant": "staging"}'
```

### Swift (iOS)

```swift
var request = URLRequest(url: URL(string: "https://anygoodie-bridge.test/api/v1/recipient/user-exists")!)
request.httpMethod = "POST"
request.setValue("<shared secret>", forHTTPHeaderField: "X-Tenant-Secret")
request.setValue("application/json", forHTTPHeaderField: "Content-Type")
request.httpBody = try? JSONEncoder().encode([
    "email": "someone@example.com",
])
```

### Kotlin (Android)

```kotlin
val json = """{"email":"someone@example.com"}"""
val requestBody = json.toRequestBody("application/json".toMediaType())

val request = Request.Builder()
    .url("https://anygoodie-bridge.test/api/v1/recipient/user-exists")
    .addHeader("X-Tenant-Secret", sharedSecret)
    .post(requestBody)
    .build()
```
