# Staging smoke (manual)

Run on **WP + WooCommerce + StoreFuse Bridge 1.0.1** before production. Base: `{site}/wp-json/storefuse/v1`.

Automated in CI: PHP syntax, PHPUnit (validator, URL safety, error envelope, version smoke). **Not** automated: live WC session, cookies, CORS from browser origin.

## Quick curl checks (replace `{site}`)

```bash
# Health
curl -sS "{site}/wp-json/storefuse/v1/status" | head -c 400

# Guest auth/me contract
curl -sS "{site}/wp-json/storefuse/v1/auth/me"

# Fresh WP REST nonce (rate limited per IP)
curl -sS "{site}/wp-json/storefuse/v1/auth/nonce"
```

## Browser / client matrix

| Check | Steps | Pass |
|-------|--------|------|
| CORS | Next.js dev origin in **API & Tools → CORS**; `OPTIONS` preflight from browser | ☐ |
| Cache flush | **API & Tools → Flush all**; confirm catalog `X-StoreFuse-Cache` refreshes | ☐ |
| Guest cart | `GET /cart` → `POST /cart/add` with `X-WC-Nonce` | ☐ |
| Checkout idempotency (retry) | Same session + same `Idempotency-Key` + same body twice → one order | ☐ |
| Idempotency isolation | Two browsers/sessions same key → must **not** share order (409 or separate orders) | ☐ |
| Pending/redirect payment | Gateway returns redirect; order stays pending/on-hold (not forced failed) | ☐ |
| Auth | `GET /auth/nonce` → login with `X-WP-Nonce` → `GET /auth/me` logged in | ☐ |
| IDOR | User A cannot `GET /orders/{id}` for User B’s order (403 forbidden envelope) | ☐ |

Record results in your release ticket; link [acceptance-gates.md](acceptance-gates.md) when all critical rows pass.

## Local XAMPP

If `{site}` is `http://localhost/...`, CORS and cookie `Secure` flags differ from production—use staging that matches production HTTPS and domain.
