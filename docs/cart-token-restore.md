# Cart token restore (staging verification)

Signed **`X-StoreFuse-Cart-Token`** (Bridge **1.0.1+** code) can restore a WooCommerce guest session when session cookies are missing. Treat this path as **experimental** until the checklist below passes on your installed Bridge version (**1.0.2+** recommended).

**Primary client path:** persist WooCommerce session cookies in a cookie jar. Send the cart token header only as a fallback.

## Token format

- **Not** a raw WooCommerce customer id.
- HMAC-signed payload bound to server-side session metadata (`StoreFuse_Bridge_Cart_Session_Token`).
- Restore runs at `rest_pre_dispatch` before cart handlers when the header is present.

## Staging matrix

Base: `{site}/wp-json/storefuse/v1`. Record Bridge version from `GET /status` → `data.version`, WP version, WC version, HPOS on/off.

| # | Step | Expected | Pass |
|---|------|----------|------|
| 1 | Guest: `GET /cart` → add item with `X-WC-Nonce` from `cart_nonce` | 200; save `Set-Cookie` and response `X-StoreFuse-Cart-Token` | ☐ |
| 2 | Clear **WC session cookies only** (keep no cart cookies); `GET /cart` with `X-StoreFuse-Cart-Token` | 200; same line items as step 1 | ☐ |
| 3 | `GET /auth/nonce` (or read `cart_nonce` from `GET /cart`); `PUT /cart/update` with token header + fresh `X-WC-Nonce` | 200; quantity change persists on next `GET /cart` | ☐ |
| 4 | New device profile: token only, no cookies, `POST /cart/add` after bootstrap nonces | 200 or documented failure — record behavior | ☐ |

**Bridge version tested:** __________  
**WooCommerce version tested:** __________  
**Result:** pass / fail / partial  
**Notes:** __________

If step 2 fails, do not document token-only continuity for Flutter; use cookie jar only until session init order is fixed on WC.

See also [staging-smoke.md](staging-smoke.md) and [verified-routes.md](verified-routes.md).
