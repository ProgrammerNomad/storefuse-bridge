# Flutter / mobile client guide

Flutter talks to the **same** JSON contract as web: `/wp-json/storefuse/v1`. Session behavior differs from Next.js - plan for a **cookie jar**, explicit nonces, and **no shared CDN cache** on authenticated calls.

**Auth SSOT:** [auth-strategy.md](../auth-strategy.md) (cookies now; Application Passwords for token-friendly mobile).

**Planning reference:** [storefuse-flutter](https://github.com/ProgrammerNomad/storefuse-flutter) docs (implemented Flutter client).

---

## HTTP client setup

- Use HTTPS to the WordPress site origin (not the storefront domain unless it proxies).
- Persist cookies per host (`Cookie` header on every request).
- Set `Cache-Control: no-store` behavior client-side for cart, auth, account, orders.
- Persist **WooCommerce session cookies** in the jar (required).
- Send **`X-StoreFuse-Cart-Token`** when cookies are unavailable (Bridge **1.0.1+** signed restore; verify on WC per [cart-token-restore.md](../cart-token-restore.md)). Prefer cookie jar; header alone is not login.
- **`minSupportedBridgeVersion`:** compare `GET /status` → `data.version` to **`1.1.0`** before guest cart flows (requires `cart_nonce` on `/auth/nonce` and `/cart`).

Packages such as `cookie_jar` + `dio` or `http` with a custom client are typical; implementation is app-specific.

---

## Auth flow (native)

Bridge 1.1: first restore the cookie session using `POST /wp-admin/admin-ajax.php` with form `action=storefuse_session`. Send `X-WP-Nonce` on authenticated GET requests as well as writes. See [the complete 1.1 contract](../release-1.1.md); the nonce-only bootstrap below is for fresh guest sessions.

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

### Request/response aliases (mobile-friendly)

| Area | Bridge canonical | Also accepted / mirrored |
|------|------------------|---------------------------|
| Cart update/remove | `cart_item_key` | `key` |
| Review POST | `content` | `review` |
| Checkout POST notes | `order_notes` | `order_comments` |
| Paginated lists | `orders`, `posts`, `reviews`, `downloads` | mirrored `items` |
| Payment methods | `data.methods[]` | (legacy bare array at `data` - prefer `methods`) |
| Redirect checkout | `redirect_url` | - |
| Shipping methods | `data.packages[].methods` | flatten client-side |
| Push tokens | `POST /account/device-token` | `token`, `platform` (`android` \| `ios`) |

Payment SDK integration is **out of Bridge scope** - Bridge returns order result JSON; native gateways may need WebView fallback per gateway.

---

## Screen customization (merchant admin → mobile UI)

The reference Flutter app reads **`GET /settings` → `data.app.ui`** (schema **`storefuse.app-ui.v1`**) at bootstrap. If `ui` is missing, the app uses built-in defaults (catalog grid, 2 columns).

| Mobile screen | WordPress admin | API / notes |
|---------------|-----------------|-------------|
| **Branding / splash** | StoreFuse → General (`app_primary_color`, support URL) | `settings.app.primary_color`, `settings.site` (name, logo) |
| **Bottom tabs** | StoreFuse → **Mobile app** → Active tabs & order (`app_ui_nav_tabs`) | `app.ui.navigation.tabs`; disable routes via **Advanced** module toggles when a tab’s API is off |
| **Drawer / header links** | StoreFuse → Navigation (optional; not used by default Flutter bottom nav) | `settings.navigation.main` |
| **Announcement bar** | General or Homepage content | `settings.header` + `GET /homepage` → `announcement_bar` |
| **Home** | Homepage (hero, categories, product rows, promo) + **Mobile app** (section order / hide) | `GET /homepage` + `app.ui.home.section_order` / `hidden` |
| **Categories list** | WooCommerce categories | Products API; list layout still app default |
| **Category products** | **Mobile app** → catalog layout / columns | `app.ui.catalog` + `screens.category` |
| **Shop** | **Mobile app** (layout, columns, shop title; catalog **stock badge** only in admin) | `app.ui.catalog`, `app.ui.screens.shop` |
| **Search** | **Mobile app** (optional search layout override) | `app.ui.screens.search` |
| **Product detail** | **Mobile app** (SKU, sticky ATC, related count) | Product JSON + `app.ui.screens.product`; related from `related_products` on single product |
| **Cart** | **Mobile app** (coupon, free-shipping bar, empty CTA) | Cart API + `settings.store.free_shipping_threshold` + `app.ui.screens.cart` |
| **Checkout** | StoreFuse → Checkout (redirect vs headless) | `GET /checkout/config`, `features.headless_checkout`, `app.ui.screens.checkout.redirect_button_label` |
| **Account / wishlist / orders** | Advanced module toggles | `GET /status` → `modules`, `features.registration` |
| **Legal / about** | General + WP privacy page | `settings.app.privacy_url`, `terms_url`, `support_url` |

Agencies can extend JSON without waiting for new admin fields via the **`storefuse_bridge_settings_response`** filter ([extensions.md](../extensions.md)).

### Minimal `app.ui` example

```json
{
  "schema": "storefuse.app-ui.v1",
  "catalog": { "layout": "grid", "columns": 2, "show_stock_badge": true },
  "screens": {
    "shop": { "title": "Products", "layout": "grid" },
    "product": { "show_sku": true, "sticky_add_to_cart": false, "related_count": 6 },
    "cart": { "show_coupon": true, "show_free_shipping_bar": true, "empty_cta_href": "/products" }
  }
}
```

---

## Password reset

Reset emails use `storefront_url` + `storefront_reset_path` from WP settings, or `storefuse_bridge_password_reset_url` for app deep links. Handle query params `key` and `login` in the app; complete reset via `POST /auth/reset-password`.

---

## Webhooks and push

Bridge outbound webhooks (ISR revalidation) are not consumed by the Flutter app directly. See [webhooks-flutter.md](webhooks-flutter.md) for signature verification vs FCM/order push patterns.

Push tokens: debug Flutter builds may call `POST /account/device-token` with a placeholder; production requires merchant FCM/APNs. See the Flutter app `docs/push-notifications.md`.

### Capability matrix (`GET /status` → `data.features`)

| Feature flag | Bridge default | Flutter behavior |
|--------------|----------------|------------------|
| `headless_checkout` | from settings | Native checkout vs redirect WebView |
| `stock_alerts` | `false` until `storefuse_bridge_stock_alerts_available` | Notify UI hidden; API returns validation error when disabled |
| `push_notifications` | `false` until `storefuse_bridge_push_available` | No token upload unless flag true **and** build defines `REGISTER_DEVICE_TOKEN=true` (optional `DEVICE_PUSH_TOKEN` stub) |
| `returns_available` | `false` until filter | Order return actions hidden |
| `invoice_available` | `false` until filter | Invoice download hidden |
| `pincode_serviceability` | `false` until filter | Pincode checker hidden on product detail |

Device tokens: after login, the app may call `POST /account/device-token` when push is enabled and `REGISTER_DEVICE_TOKEN` is set at compile time. Delivery still requires merchant FCM/APNs wiring via a companion plugin.

---

## Merchant customization matrix (screens → admin → API)

Use this when explaining what store owners can change **without** shipping a new app build. Presentation flags live in **`GET /settings` → `data.app.ui`** (schema `storefuse.app-ui.v1`). Content blocks for Home still come from **`GET /homepage`**.

| App screen | WordPress admin (StoreFuse) | API the app reads |
|------------|----------------------------|-------------------|
| **Splash / theme** | **General** - app primary colour, support URL; WP **Customizer** - site name, logo | `GET /settings` → `app.primary_color`, `site.*`, `app.support_url`, legal URLs |
| **Bottom tabs** | **Mobile app** - Active tabs & order (`app_ui_nav_tabs`); **Advanced** - disable modules when a tab’s API must be off | `app.ui.navigation.tabs`; `GET /status` → `modules` |
| **Drawer / header links** | **Navigation** - optional `storefuse-header` menu; **General** - announcement bar | `GET /settings` → `navigation.main`, `header.*` |
| **Home** | **Homepage** - hero, categories, product rows, promo; **Mobile app** - section order / hide sections | `GET /homepage` (ordered/filtered); layout hints also in `app.ui.screens.home` |
| **Shop (`/products`)** | **Mobile app** - catalog layout, columns, stock badge, shop title | `GET /settings` → `app.ui.catalog`, `app.ui.screens.shop` |
| **Search** | **Mobile app** - optional search layout override | `app.ui.screens.search` (falls back to catalog) |
| **Category products** | **Mobile app** - same catalog defaults as Shop | `app.ui.catalog` |
| **Product detail (PDP)** | **Mobile app** - SKU, sticky ATC, related count | `app.ui.screens.product`; product JSON from `GET /products/{slug}` (`is_in_stock`, `stock_status`) |
| **Cart** | **Mobile app** - coupon, free-shipping bar, empty CTA; **General** - free shipping threshold label/amount | `app.ui.screens.cart`, `store.free_shipping_threshold*` |
| **Checkout** | **Checkout** - redirect vs headless, button label | `GET /checkout/config`, `GET /status` → `features.headless_checkout` |
| **Account / orders / wishlist** | **Advanced** - module toggles | `GET /status` → `modules` |
| **About / legal** | **General** - support URL; WP privacy/terms pages | `GET /settings` → `app.privacy_url`, `app.terms_url`, `app.support_url` |

**Defaults:** If `app.ui` is missing (older Bridge), the reference app uses built-in layout defaults (2-column product grid for catalog screens after Phase 0).

**Extensions:** Agencies can inject UI keys before admin fields exist via filter `storefuse_bridge_settings_response` ([extensions.md](../extensions.md)).

---

## Testing notes

- Test on real device/emulator against staging WP.
- Do not assume cookies match the user’s browser session on the same machine.
- Record results in [verified-routes.md](../verified-routes.md) manual checklist.
