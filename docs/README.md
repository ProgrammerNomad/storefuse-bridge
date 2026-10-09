# StoreFuse Bridge documentation

Headless WooCommerce API for **Next.js**, **Flutter**, and other clients - one namespace: `/wp-json/storefuse/v1`.

| Document | Purpose |
|----------|---------|
| [admin-guide.md](admin-guide.md) | **wp-admin** - settings pages and API field registry |
| [verified-routes.md](verified-routes.md) | **Source of truth** - routes, auth tiers, session test checklist |
| [api-reference.md](api-reference.md) | Payload shapes and endpoint details |
| [architecture.md](architecture.md) | Plugin internals, caching, modules |
| [clients/nextjs.md](clients/nextjs.md) | Browser cookies, CORS, ISR |
| [clients/mobile-flutter.md](clients/mobile-flutter.md) | Native session, cart token, nonces |
| [clients/generic-web.md](clients/generic-web.md) | SPA / other web clients |
| [extensions.md](extensions.md) | WordPress filters and actions (v0.1) |
| [extension-api-v0.2.md](extension-api-v0.2.md) | Planned module registration spec (doc only) |

**Minimum Bridge version for client docs:** `0.1.0` (`STOREFUSE_BRIDGE_VERSION`).

Related: [storefuse-flutter](https://github.com/ProgrammerNomad/storefuse-flutter) planning docs (no shippable app in this phase).
