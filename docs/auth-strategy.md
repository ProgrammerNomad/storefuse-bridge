# Auth strategy — web and mobile

StoreFuse Bridge supports phased authentication paths while keeping **one REST contract** (`/storefuse/v1`).

## Phase 1 — Now (production default)

| Client | Mechanism | Notes |
|--------|-----------|--------|
| **Next.js / browser** | WordPress **auth cookies** + `credentials: 'include'` | Bootstrap `X-WP-Nonce` via `GET /auth/nonce`; cart writes use `X-WC-Nonce` |
| **Flutter / native** | **Cookie jar** on the WP host + `flutter_secure_storage` for **cart token** only | Same endpoints as web; session endpoints are never CDN-cached |

`GET /auth/me` returns **`200`** with `{ "logged_in": false }` for guests (no 401 probe).

## Phase 2 — Application Passwords (v0.4+)

For mobile apps that cannot rely on cookie UX:

1. User creates an **Application Password** in WordPress (Users → Profile).
2. App sends **`Authorization: Basic`** `base64(username:application_password)` **or** **`Authorization: Bearer`** with the same base64 payload on StoreFuse routes.
3. Bridge authenticates via `wp_authenticate_application_password` before route handlers run.
4. Cart/checkout may still require WooCommerce session cookies — prefer login cookie flow for cart-heavy apps or hybrid BFF.

## Phase 3 — Optional JWT (v1.0+ spec only)

A dedicated JWT or token exchange namespace may be added when there is demand. Until then, document-only; do not duplicate auth truth in client repos.

## Client guides

- [clients/nextjs.md](clients/nextjs.md)
- [clients/mobile-flutter.md](clients/mobile-flutter.md)
- Flutter repo: [auth-and-session.md](https://github.com/ProgrammerNomad/storefuse-flutter/blob/main/docs/auth-and-session.md) (links here as SSOT)
