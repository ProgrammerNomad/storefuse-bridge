# CORS policy (StoreFuse Bridge)

Bridge CORS is implemented in `storefuse-bridge/includes/class-cors.php` and applies **only** to routes under `/wp-json/storefuse/v1/*`.

## Default (recommended for native apps)

- **Setting:** `cors_enabled` = off (default in General → Advanced).
- **Behavior:** Bridge removes WordPress core permissive `Access-Control-*` headers for StoreFuse routes and does **not** reflect origins.
- **Native Flutter / mobile:** Uses cookie jar on device, not browser CORS - leave CORS disabled unless you run a browser SPA on another origin.

## Browser SPA mode

1. Enable **CORS** in StoreFuse → General → Advanced.
2. Add one origin per line in **Allowed origins** (exact scheme + host + port), e.g. `https://shop.example.com`.
3. Only listed origins receive:
   - `Access-Control-Allow-Origin: <origin>`
   - `Access-Control-Allow-Credentials: true`
   - Methods and headers needed for Bridge (including `X-WP-Nonce`, `X-WC-Nonce`, `Idempotency-Key`).

Denied or missing origins get **no** `Access-Control-Allow-Origin` (integration test in `scripts/integration-local.py`).

## Interaction with WordPress core

WordPress may emit broad REST CORS headers early. Bridge strips those suffixes for StoreFuse routes and replaces them when CORS is enabled and the origin matches the allowlist.

## Verification

```bash
# Denied origin (CORS off or not allowlisted)
curl -sI -H "Origin: https://untrusted.example" http://localhost/wp-json/storefuse/v1/status | findstr Access-Control

# Allowlisted (after enabling + saving origin)
curl -sI -H "Origin: https://your-spa.example" http://localhost/wp-json/storefuse/v1/status
```

See also [clients/generic-web.md](clients/generic-web.md).
