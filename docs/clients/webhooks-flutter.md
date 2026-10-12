# Webhooks and mobile (Flutter)

StoreFuse Bridge does **not** ingest payment-gateway webhooks. Bridge webhooks are **outbound from WordPress** to your storefront (ISR revalidation, cache busting).

## What Bridge sends

When products, categories, or settings change, Bridge may `POST` to the **Storefront revalidation URL** configured in wp-admin (Storefront & Clients). Payloads are JSON; requests include:

| Header | Purpose |
|--------|---------|
| `X-StoreFuse-Signature` | `sha256=` HMAC of body using **Revalidation secret** |
| `Content-Type` | `application/json` |

Verify the signature before rebuilding cache. SSRF protections and HTTPS-only URLs are enforced server-side ([security.md](../security.md)).

## Flutter / FCM (push notifications)

Bridge v1 does **not** expose a customer push API. Typical pattern:

1. **WordPress** - WooCommerce order status hooks or a small custom plugin enqueue FCM via your provider.
2. **Flutter** - Register device tokens against **your** backend (not Bridge `/storefuse/v1`).
3. **Optional** - On product restock, consume `POST /products/{slug}/notify` signups from WP admin/meta (Bridge stores signups; delivery is store-specific).

Bridge **`GET /status`** `modules.webhooks` indicates whether the outbound revalidation module is enabled - not mobile push.

## Related docs

- [nextjs.md](nextjs.md) - ISR consumer
- [checkout-payments.md](checkout-payments.md) - async payment vs storefront webhooks
- [mobile-flutter.md](mobile-flutter.md) - session and checkout client
