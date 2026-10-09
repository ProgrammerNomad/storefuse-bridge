# Next.js client guide

StoreFuse Bridge is the **only** commerce API your Next.js app should call: `/wp-json/storefuse/v1/*`.

Auth overview (cookies today; shared with mobile doc): [auth-strategy.md](../auth-strategy.md).

Checkout and pending/async payments: [checkout-payments.md](checkout-payments.md).

---

## Architecture patterns

| Pattern | When |
|---------|------|
| **Direct browser → WP** | Same-site or CORS-enabled WP; `fetch(..., { credentials: 'include' })` |
| **BFF / Route Handler proxy** | Hide WP origin, attach cookies server-side, simplify CORS |
| **ISR / static catalog** | Public routes only (`/products`, `/settings`, …) |

Never cache responses that include cart, auth, or account data at the CDN edge.

---

## Public vs session fetches

```ts
// Public - safe for ISR (example)
const products = await fetch(`${WOO_URL}/wp-json/storefuse/v1/products`, {
  next: { revalidate: 600 },
});

// Session - always dynamic, credentials required
const cart = await fetch(`${WOO_URL}/wp-json/storefuse/v1/cart`, {
  cache: 'no-store',
  credentials: 'include',
});
```

See [verified-routes.md](../verified-routes.md) for the full tier list.

---

## Auth flow (browser)

1. `GET /auth/nonce` → read nonce for `X-WP-Nonce`.
2. `POST /auth/login` with `{ email, password }`, headers: `X-WP-Nonce`, `Content-Type: application/json`, `credentials: 'include'`.
3. Response includes user object with `nonce` and `cart_nonce`; Set-Cookie establishes WP auth.
4. `GET /auth/me` with cookies for profile refresh.

Guest cart before login: ensure WC session cookie from prior `GET /cart` is sent on login so [guest merge](../architecture.md) runs.

---

## Cart and checkout writes

Use **`X-WC-Nonce`** (not `X-WP-Nonce`) for:

- `POST /cart/add`, `PUT /cart/update`, `DELETE /cart/remove`, coupon routes
- `POST /checkout`

Obtain `cart_nonce` from login/me or bootstrap flow documented in [api-reference.md](../api-reference.md).

---

## Checkout modes

Read `GET /status` → `features.headless_checkout` and `GET /checkout/config` → `checkout_mode`:

| Mode | Client behavior |
|------|-----------------|
| `redirect` | `POST /checkout/redirect-url` → redirect user to Woo checkout |
| `headless` | Collect fields client-side → `POST /checkout` |

---

## ISR webhooks

Enable **ISR Revalidation Webhooks** in WP admin (Advanced). Bridge POSTs to `{storefront_url}/api/revalidate` when catalog/settings change. Match `revalidation_secret` with your Next.js env.

---

## Differences from Flutter

Browsers send cookies automatically with `credentials: 'include'`. CORS must allow your storefront origin. Flutter uses a manual cookie jar - see [mobile-flutter.md](mobile-flutter.md).
