# Staging smoke (manual)

Run on **WP + WooCommerce + StoreFuse Bridge 1.0.2** before production. Base: `{site}/wp-json/storefuse/v1`.

Record on the release ticket: **Bridge** `version`, **`build_id`** / **`build_time`** (after [deploy-local.ps1](../scripts/deploy-local.ps1)), **WordPress** and **WooCommerce** versions from `GET /status`, **HPOS** on/off. Full checklist: [BASELINE.md](BASELINE.md).

Automated in CI: PHP syntax, PHPUnit (validator, URL safety, error envelope, version smoke). **Not** automated: live WC session, cookies, CORS from browser origin.

## Quick curl checks (replace `{site}`)

```bash
# Health
curl -sS "{site}/wp-json/storefuse/v1/status" | head -c 400

# Guest auth/me contract
curl -sS "{site}/wp-json/storefuse/v1/auth/me"

# Fresh WP REST + cart nonces (rate limited per IP; 1.0.2+ includes cart_nonce)
curl -sS "{site}/wp-json/storefuse/v1/auth/nonce"

# Guest cart bootstrap (1.0.2+)
curl -sS -c cookies.txt "{site}/wp-json/storefuse/v1/cart"
curl -sS -b cookies.txt "{site}/wp-json/storefuse/v1/auth/nonce"
```

## Browser / client matrix

| Check | Steps | Pass |
|-------|--------|------|
| CORS | Next.js dev origin in **API & Tools → CORS**; `OPTIONS` preflight from browser | ☐ |
| Cache flush | **API & Tools → Flush all**; confirm catalog `X-StoreFuse-Cache` refreshes | ☐ |
| Guest cart | `GET /cart` → `GET /auth/nonce` → `POST /cart/add` with `X-WC-Nonce` + cookies (**1.0.2+**) | ☐ |
| Cart token restore | [cart-token-restore.md](cart-token-restore.md) matrix | ☐ |
| Cart merge | Guest lines → login (same jar) → qty/coupon cases per [verified-routes.md](verified-routes.md) | ☐ |
| Checkout idempotency (retry) | Same session + same `Idempotency-Key` + same body twice → one order | ☐ |
| Idempotency isolation | Two browsers/sessions same key → must **not** share order (409 or separate orders) | ☐ |
| Pending/redirect payment | Gateway returns redirect; order stays pending/on-hold (not forced failed) | ☐ |
| Auth | `GET /auth/nonce` → login with `X-WP-Nonce` → `GET /auth/me` logged in | ☐ |
| IDOR | User A cannot `GET /orders/{id}` for User B’s order (403 forbidden envelope) | ☐ |

Record results in your release ticket; link [acceptance-gates.md](acceptance-gates.md) when all critical rows pass.

## Local XAMPP

1. Sync plugin: `./scripts/deploy-local.ps1 -WordPressPath C:\xampp\htdocs` (adjust if WP lives in a subfolder).
2. Optional fixtures: `php scripts/local-fixtures.php C:\xampp\htdocs "$env:TEMP\storefuse-fixtures"`.
3. Confirm `data.build_id` in `GET /status` matches the deploy script output.

If `{site}` is `http://localhost/...`, CORS and cookie `Secure` flags differ from production-use staging that matches production HTTPS and domain.
