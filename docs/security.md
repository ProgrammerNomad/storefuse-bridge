# Security — StoreFuse Bridge (lightweight threat model)

## Assets

- Customer PII (billing/shipping, email, order history)
- WordPress auth sessions and WooCommerce cart sessions
- Store admin settings (CORS origins, storefront webhook URL, revalidation secret)
- Order keys used on thank-you pages

## Trust boundaries

| Boundary | Risk | Mitigations |
|----------|------|-------------|
| Public REST catalog | Scraping, cache poisoning | Public cache only on catalog; `no-store` on session routes |
| Auth endpoints | Credential stuffing | Rate limits (`storefuse_bridge_auth_max_attempts` filter); login failure actions |
| Cart/checkout writes | CSRF | `X-WC-Nonce` / `X-WP-Nonce`; CORS credentials only for allowed origins |
| Account/order routes | IDOR | Ownership checks in orders module; admin capability bypass |
| Outbound webhooks | SSRF | HTTPS-only storefront URL; reject unresolvable hosts (empty DNS); block private/reserved IPs; `redirection => 0` on outbound POST |
| Notify signup | Spam / GDPR | Consent flag, honeypot field, IP rate limit |
| Order key URLs | Token leakage | Treat order key as secret; logged-in users cannot read other customers’ orders |

## Logging

Checkout and webhook failures log via WooCommerce logger (`storefuse-bridge` source). Passwords, nonces, and raw tokens are never logged.

## Checkout idempotency

`Idempotency-Key` is scoped to the WooCommerce session or WordPress user. Cached replay responses **omit `order_key`** to reduce cross-client leakage if a key is reused maliciously. Mismatching checkout fingerprints return `409 idempotency_key_reused`.

## Cart session token

`X-StoreFuse-Cart-Token` (1.0.1+ code) is an **HMAC-signed** value bound to a server-side session record — not a raw WooCommerce customer id. Treat token-only restore as **experimental** until [cart-token-restore.md](cart-token-restore.md) passes. Prefer WooCommerce cookies via a cookie jar when possible.

## Rate limiting and IP

IP throttling uses `REMOTE_ADDR` unless `storefuse_bridge_trusted_proxies` lists your reverse proxy. Do not trust `X-Forwarded-For` without that configuration.

## Webhook SSRF (residual)

URL validation at send time reduces SSRF; DNS rebinding/time-of-check-time-of-use risks remain — use a fixed storefront hostname and monitor outbound calls.

## Mobile auth

See [auth-strategy.md](auth-strategy.md) for cookie vs Application Password trade-offs.
