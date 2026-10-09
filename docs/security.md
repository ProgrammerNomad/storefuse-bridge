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
| Outbound webhooks | SSRF | HTTPS-only storefront URL; block private/reserved IPs before `wp_remote_post` |
| Notify signup | Spam / GDPR | Consent flag, honeypot field, IP rate limit |
| Order key URLs | Token leakage | Treat order key as secret; logged-in users cannot read other customers’ orders |

## Logging

Checkout and webhook failures log via WooCommerce logger (`storefuse-bridge` source). Passwords, nonces, and raw tokens are never logged.

## Mobile auth

See [auth-strategy.md](auth-strategy.md) for cookie vs Application Password trade-offs.
