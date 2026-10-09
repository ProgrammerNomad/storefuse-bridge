# Generic web client guide

Any JavaScript or SPA storefront (Vue, Svelte, static site + JS) can use StoreFuse Bridge as the commerce backend.

---

## Rules

1. **One API:** `/wp-json/storefuse/v1/*` only - no WooCommerce REST keys in the browser.
2. **Two cache classes:** public catalog (cacheable) vs session/customer (`no-store`).
3. **Two nonce types:** `X-WP-Nonce` for auth/account writes; `X-WC-Nonce` for cart/checkout.

Route list: [verified-routes.md](../verified-routes.md).

---

## CORS and origins

WordPress must allow your web origin for credentialed requests if the SPA is on a different host. Prefer:

- Same-origin reverse proxy (`/api/storefuse → WP`), or
- Server-side BFF that holds no secrets except optional revalidation keys.

Never expose WooCommerce consumer key/secret in frontend bundles.

---

## Minimal bootstrap

```http
GET /wp-json/storefuse/v1/status
GET /wp-json/storefuse/v1/settings
```

Use `GET /products` and related public routes for catalog. Initialize cart with `GET /cart` before add-to-cart.

---

## Security checklist

- [ ] Session endpoints use `cache: 'no-store'` (or equivalent).
- [ ] HTTPS only in production.
- [ ] Nonces refreshed after login (`GET /auth/me`).
- [ ] Password reset links use configured storefront URL, not wp-login.php (when settings set).

---

## Related guides

- [nextjs.md](nextjs.md) - ISR and App Router specifics
- [mobile-flutter.md](mobile-flutter.md) - native session model (contrast with browser)
