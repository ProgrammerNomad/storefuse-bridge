# StoreFuse Bridge - WordPress admin guide

Store owners configure StoreFuse Bridge under **WooCommerce → StoreFuse**. This guide points to the current screen reference and the proposed admin refactor.

## Documentation

- [Admin documentation index](admin/README.md)
- [Current admin screens and slugs](admin/current/)
- [Refactor v2 plan and page specifications](admin/refactor-v2/)
- [Settings field registry](admin/fields-registry.md) - option keys, current and target pages, and API consumers
- [Flutter client setup](clients/mobile-flutter.md)

**Permission:** `manage_woocommerce` by default; the plugin exposes a filter to customize the capability. **Settings option:** `storefuse_bridge_settings`.

## Where settings live

Bridge-managed values are stored together in the existing `storefuse_bridge_settings` WordPress option. The planned admin refactor rearranges where those fields appear; it does not require a database migration, backfill, or conversion.

Payment gateways remain configured in **WooCommerce → Settings → Payments**. Site name, tagline, logo, and favicon remain WordPress Customizer settings. Navigation menus remain managed by WordPress.

## Quick troubleshooting

| Symptom | Check |
|---|---|
| Browser storefront is blocked by CORS | In current UI, check **API & Tools → CORS**. Add the exact origin including scheme and port, and confirm the client sends credentialed requests when using a logged-in session. |
| Cart writes return `invalid_nonce` | Fetch a fresh cart/session nonce and send `X-WC-Nonce` on cart and checkout writes. See [verified routes](verified-routes.md). |
| Login or account writes fail with 403 | Follow the documented `X-WP-Nonce` flow in [authentication strategy](auth-strategy.md). |
| Catalog appears stale | Use the current API & Tools cache controls or save the relevant Bridge settings; see [cache and architecture notes](architecture.md). |
| Flutter loses its cart/session | Keep the WooCommerce cookies in the app cookie jar and follow [the Flutter client guide](clients/mobile-flutter.md). |
| ISR webhook does not reach the storefront | Check the storefront URL, revalidation path, secret, and Webhooks module. Review [security guidance](security.md) if the destination is rejected. |

For manual verification, see [staging smoke checks](staging-smoke.md) and [production acceptance gates](acceptance-gates.md).
