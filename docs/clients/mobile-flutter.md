# Flutter / mobile client guide

Flutter talks to the **same** JSON contract as web: `/wp-json/storefuse/v1`. Session behavior differs from Next.js - plan for a **cookie jar**, explicit nonces, and **no shared CDN cache** on authenticated calls.

**Auth SSOT:** [auth-strategy.md](../auth-strategy.md) (cookies now; Application Passwords for token-friendly mobile).

**Planning reference:** [storefuse-flutter](https://github.com/ProgrammerNomad/storefuse-flutter) docs (roadmap only until app code ships).

---

## HTTP client setup

- Use HTTPS to the WordPress site origin (not the storefront domain unless it proxies).
- Persist cookies per host (`Cookie` header on every request).
- Set `Cache-Control: no-store` behavior client-side for cart, auth, account, orders.
- Persist **WooCommerce session cookies** in the jar (required).
- Send **`X-StoreFuse-Cart-Token`** when cookies are unavailable (Bridge **1.0.1+** signed restore; verify on WC per [cart-token-restore.md](../cart-token-restore.md)). Prefer cookie jar; header alone is not login.
- **`minSupportedBridgeVersion`:** compare `GET /status` → `data.version` to **`1.0.2`** before guest cart flows (requires `cart_nonce` on `/auth/nonce` and `/cart`).

Packages such as `cookie_jar` + `dio` or `http` with a custom client are typical; implementation is app-specific.

---

## Auth flow (native)

1. `GET /auth/nonce` - no cookies required; read `nonce` and `cart_nonce` (**1.0.2+**).
2. `POST /auth/login` with header `X-WP-Nonce: {nonce}`; store all `Set-Cookie` headers in the jar.
3. `GET /auth/me` - verify session; refresh `nonce` and `cart_nonce` from payload (`logged_in: false` still includes nonces on **1.0.2+**).

**Application Passwords (optional):** prefer `Authorization: Basic` with `username:app_password`. Bearer + base64 is compatibility-only (not OAuth2).

Logout: `POST /auth/logout` with `X-WP-Nonce` and auth cookies.

---

## Guest cart → login merge

1. `GET /cart` as guest - save WC session cookies + optional cart token header; read `cart_nonce` from body (**1.0.2+**).
2. Add items with `X-WC-Nonce` after `GET /auth/nonce` or `GET /cart` bootstrap (or post-login `me`).
3. `POST /auth/login` **with the same cookie jar** so `storefuse_bridge_guest_cart_merged` can merge lines.

Validate on a dev site using the checklist in [verified-routes.md](../verified-routes.md).

---

## Cart and checkout headers

| Operation | Header |
|-----------|--------|
| Cart/checkout writes | `X-WC-Nonce` |
| Account, wishlist, order cancel | `X-WP-Nonce` |
| Reorder | `X-WC-Nonce` (handler-enforced) |

---

## Checkout

- Read `features.headless_checkout` from `GET /status`.
- Redirect mode: `POST /checkout/redirect-url` → open returned URL in `WebView` or external browser.
- Headless mode: `POST /checkout` with billing/shipping/payment payloads per [api-reference.md](../api-reference.md).

Payment SDK integration is **out of Bridge scope** - Bridge returns order result JSON; native gateways may need WebView fallback per gateway.

---

## Password reset

Reset emails use `storefront_url` + `storefront_reset_path` from WP settings, or `storefuse_bridge_password_reset_url` for app deep links. Handle query params `key` and `login` in the app; complete reset via `POST /auth/reset-password`.

---

## Testing notes

- Test on real device/emulator against staging WP.
- Do not assume cookies match the user’s browser session on the same machine.
- Record results in [verified-routes.md](../verified-routes.md) manual checklist.
