# Verified routes (StoreFuse Bridge v1.0.0)

This matrix is audited from `register_rest_route()` calls under the `storefuse/v1` namespace in PHP. Use it as the source of truth for client docs and Flutter phase planning.

**Base URL:** `{site}/wp-json/storefuse/v1`

**Plugin version audited:** `1.0.0` (`STOREFUSE_BRIDGE_VERSION`)

---

## Auth tiers

| Tier | Meaning | Typical headers |
|------|---------|-----------------|
| **Public** | No login; permission callback allows anonymous access | Optional `Cookie` for personalization only |
| **Session (read)** | WooCommerce session; no write nonce | `Cookie` (WC session), response may include `X-StoreFuse-Cart-Token` |
| **Session (write)** | Cart/checkout mutations | `Cookie` + `X-WC-Nonce` (action `wc_store_api`) |
| **Auth (write)** | Login/register/logout/password flows | `X-WP-Nonce` (action `wp_rest`; bootstrap via `GET /auth/nonce`) |
| **Authenticated** | Logged-in WordPress user | `Cookie` (auth + WC session) |
| **Authenticated + WP nonce** | Logged-in + CSRF on writes | `Cookie` + `X-WP-Nonce` |

Some routes use `permission_callback => __return_true` but enforce login or nonce **inside the handler** (noted below as “handler enforces”).

---

## Response headers (by tier)

| Header | When |
|--------|------|
| `X-StoreFuse-Bridge-Version` | All StoreFuse envelope responses |
| `Cache-Control: public, max-age=…` | Public catalog/settings (see caching table) |
| `Cache-Control: no-store` | Cart, checkout, auth, account, orders, wishlist |
| `X-StoreFuse-Cart-Token` | Cart and post-login auth responses (WC customer/session id) |
| `X-StoreFuse-Cache: HIT \| MISS` | Transient-backed public endpoints |

Never cache session or authenticated responses on shared CDNs.

---

## Route matrix

| Method | Route | Module | Auth tier | Notes |
|--------|-------|--------|-----------|-------|
| GET | `/status` | status | Public | Short public cache (`max-age=60`) |
| GET | `/settings` | settings | Public | Cached |
| GET | `/navigation` | settings | Public | Cached |
| GET | `/homepage` | settings | Public | Cached |
| GET | `/products` | products | Public | Cached |
| GET | `/products/{slug}` | products | Public | Cached |
| POST | `/products/{slug}/notify` | products | Public | Requires `consent: true`; honeypot `website`; rate limited |
| GET | `/categories` | categories | Public | Cached |
| GET | `/categories/{slug}` | categories | Public | Cached |
| GET | `/search` | search | Public | Cached |
| GET | `/attributes` | attributes | Public | Cached |
| GET | `/tags` | tags | Public | Cached |
| GET | `/posts` | posts | Public | Module `posts`; cached |
| GET | `/posts/{slug}` | posts | Public | Cached |
| GET | `/reviews` | reviews | Public | Query `product_id`; cached |
| POST | `/reviews` | reviews | Authenticated + WP nonce | Handler enforces login + nonce |
| GET | `/utils/countries` | utils | Public | |
| GET | `/utils/pincode/{pincode}` | utils | Public | |
| GET | `/auth/nonce` | auth | Public | Returns fresh `wp_rest` nonce; rate limited per IP |
| POST | `/auth/register` | auth | Auth (write) | Sets auth cookie on success |
| POST | `/auth/login` | auth | Auth (write) | Guest cart merge via `StoreFuse_Bridge_Session` |
| POST | `/auth/logout` | auth | Auth (write) | Handler requires login |
| GET | `/auth/me` | auth | Public | Returns `200` with `{ logged_in: false }` for guests; full profile when logged in |
| POST | `/auth/forgot-password` | auth | Auth (write) | |
| POST | `/auth/reset-password` | auth | Auth (write) | |
| GET | `/cart` | cart | Session (read) | `no-store`; cart token header |
| POST | `/cart/add` | cart | Session (write) | |
| PUT | `/cart/update` | cart | Session (write) | |
| DELETE | `/cart/remove` | cart | Session (write) | |
| POST | `/cart/coupon` | cart | Session (write) | |
| DELETE | `/cart/coupon` | cart | Session (write) | |
| GET | `/checkout/config` | checkout | Public | `no-store` |
| GET | `/checkout/payment-methods` | checkout | Public | `no-store` |
| GET | `/checkout/shipping-methods` | checkout | Public | `no-store` |
| POST | `/checkout` | checkout | Session (write) | Requires WC session + `X-WC-Nonce` |
| POST | `/checkout/redirect-url` | checkout | Session (write) | Requires WC session + `X-WC-Nonce` |
| GET | `/orders/{key}` | checkout | Public | Key pattern `wc_order_*`; thank-you page |
| GET | `/account` | account | Authenticated | |
| PUT | `/account` | account | Authenticated + WP nonce | |
| POST | `/account/change-password` | account | Authenticated + WP nonce | |
| GET | `/orders` | orders | Authenticated | |
| GET | `/orders/{id}` | orders | Authenticated | Numeric id only |
| POST | `/orders/{id}/cancel` | orders | Authenticated + WP nonce | |
| POST | `/orders/{id}/reorder` | orders | Authenticated + WC nonce | Handler checks `X-WC-Nonce` |
| POST | `/orders/{id}/return-request` | orders | Authenticated + WP nonce | |
| GET | `/orders/{id}/tracking` | orders | Authenticated | |
| GET | `/orders/{id}/invoice` | orders | Authenticated | |
| GET | `/addresses` | addresses | Authenticated | |
| PUT | `/addresses/billing` | addresses | Authenticated + WP nonce | |
| PUT | `/addresses/shipping` | addresses | Authenticated + WP nonce | |
| GET | `/wishlist` | wishlist | Authenticated | |
| POST | `/wishlist/add` | wishlist | Authenticated + WP nonce | |
| DELETE | `/wishlist/remove` | wishlist | Authenticated + WP nonce | |
| GET | `/downloads` | downloads | Authenticated | |

Disabled modules do not register routes (404 from WordPress REST router).

---

## Manual session verification checklist

Run against a **dev** WordPress + WooCommerce site with StoreFuse Bridge active. Full staging script: [staging-smoke.md](staging-smoke.md). Record pass/fail, Bridge version, and WC version.

| # | Step | Expected | Result |
|---|------|----------|--------|
| 1 | `GET /cart` as guest (no cookies) | 200, empty or new cart, `X-StoreFuse-Cart-Token` set | ☐ |
| 2 | `GET /auth/nonce` | 200 with nonce for `X-WP-Nonce` | ☐ |
| 3 | Obtain WC cart nonce (from cart response or login) | Nonce valid for `wc_store_api` | ☐ |
| 4 | `POST /cart/add` with `X-WC-Nonce` + session cookie | Item added | ☐ |
| 5 | `PUT /cart/update` | Quantity updates | ☐ |
| 6 | `POST /auth/login` with guest cart cookie + `X-WP-Nonce` | 200, user payload, cart merged | ☐ |
| 7a | `GET /auth/me` without auth cookie | 200 `{ logged_in: false }` | ☐ |
| 7b | `GET /auth/me` with auth cookie | 200 profile + `logged_in: true` | ☐ |
| 8 | `POST /auth/logout` with `X-WP-Nonce` | Session cleared | ☐ |

**Bridge version tested:** __________  
**WooCommerce version tested:** __________  
**Notes:** __________

---

## Client header matrix (summary)

| Concern | Next.js (browser) | Flutter (native) |
|---------|-------------------|------------------|
| Auth cookie | Browser sends automatically with `credentials: 'include'` | Persist cookie jar per site; same cookie names as WP |
| WC session | Same-origin or BFF proxy to WP origin | Cookie jar + optional `X-StoreFuse-Cart-Token` storage |
| Cart/checkout writes | `X-WC-Nonce` from login/me or cart bootstrap | Same header; store nonces from `/auth/me` |
| Auth writes | `GET /auth/nonce` then `X-WP-Nonce` | Same flow; no shared storage with browser |
| CORS | Configure WP/plugin for storefront origin | Not applicable for direct mobile → WP HTTPS |
| Caching | ISR only for public tier; never cache credentialed fetches | No HTTP cache on authenticated requests |

See [clients/nextjs.md](clients/nextjs.md) and [clients/mobile-flutter.md](clients/mobile-flutter.md).
