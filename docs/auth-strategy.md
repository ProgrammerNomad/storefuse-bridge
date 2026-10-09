# Auth strategy — web and mobile

StoreFuse Bridge supports phased authentication paths while keeping **one REST contract** (`/storefuse/v1`).

## Supported flows (v1.0.1)

### Next.js / browser

- WordPress **auth cookies** with `credentials: 'include'` on same-site or correctly configured cross-site setups.
- Bootstrap **`X-WP-Nonce`** via `GET /auth/nonce` before auth writes; **`X-WC-Nonce`** for cart/checkout writes.
- Cross-origin SPAs often need a **BFF** (Route Handler) to forward cookies, or aligned **SameSite** + subdomain strategy. WordPress default cookie attributes may block cross-site browser requests.

### Flutter / native

- **Required:** a **cookie jar** on the WordPress/WooCommerce host (Woo session + WP auth cookies).
- **`X-StoreFuse-Cart-Token`:** signed server token (v1.1+) that can restore the Woo session when sent on requests **without** a session cookie; still send **`X-WC-Nonce`** for cart/checkout writes after `GET /cart`.
- Do **not** treat the header alone as auth; it is session continuity, not login.

### Application Passwords (account API access)

1. User creates an **Application Password** in wp-admin (Users → Profile).
2. Prefer **`Authorization: Basic`** with standard base64 `username:application_password`.
3. **`Authorization: Bearer`** with the same base64 payload is supported for compatibility only — this is **not** OAuth2 or an opaque access token.
4. App Passwords authenticate **WordPress user** context; cart/checkout still need WooCommerce session + nonces unless cookies are also persisted.

## Non-goals

- `GET /auth/nonce` is **not** bot protection or authentication — rate limited only.
- Custom Bearer tokens without Application Passwords are **not** supported (JWT spec-only below).

## Phase 3 — Optional JWT (future)

Document-only until demand; do not duplicate auth truth in client repos.

## Client guides

- [clients/nextjs.md](clients/nextjs.md)
- [clients/mobile-flutter.md](clients/mobile-flutter.md)
- Flutter repo: [auth-and-session.md](https://github.com/ProgrammerNomad/storefuse-flutter/blob/main/docs/auth-and-session.md) (links here as SSOT)

`GET /auth/me` returns **`200`** with `{ "logged_in": false }` for guests.
