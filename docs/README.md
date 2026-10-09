# StoreFuse Bridge documentation

Headless WooCommerce API for **Next.js**, **Flutter**, and other clients - one namespace: `/wp-json/storefuse/v1`.

| Document | Purpose |
|----------|---------|
| [admin-guide.md](admin-guide.md) | **wp-admin** - settings pages and API field registry |
| [verified-routes.md](verified-routes.md) | **Source of truth** - routes, auth tiers, session test checklist |
| [api-reference.md](api-reference.md) | Payload shapes and endpoint details |
| [architecture.md](architecture.md) | Plugin internals, caching, modules |
| [auth-strategy.md](auth-strategy.md) | Web + mobile auth phases (SSOT) |
| [security.md](security.md) | Threat model and hardening notes |
| [openapi.yaml](openapi.yaml) | OpenAPI 3.1 skeleton |
| [release-checklist.md](release-checklist.md) | Ship and upgrade checklist |
| [release-bar.md](release-bar.md) | v1.0 release decision (audit follow-up) |
| [acceptance-gates.md](acceptance-gates.md) | 14-gate production scorecard |
| [staging-smoke.md](staging-smoke.md) | Manual smoke on WP+WC staging |
| [compatibility-matrix.md](compatibility-matrix.md) | WP / WC / PHP support |
| [clients/checkout-payments.md](clients/checkout-payments.md) | Async / pending payment client flow |
| [clients/nextjs.md](clients/nextjs.md) | Browser cookies, CORS, ISR |
| [clients/mobile-flutter.md](clients/mobile-flutter.md) | Native session, cart token, nonces |
| [clients/generic-web.md](clients/generic-web.md) | SPA / other web clients |
| [extensions.md](extensions.md) | WordPress filters and actions (v0.1) |
| [extension-api-v0.2.md](extension-api-v0.2.md) | Planned module registration spec (doc only) |

**Minimum Bridge version for client docs:** `1.0.2` (`STOREFUSE_BRIDGE_VERSION`).

## Auth and session SSOT (headers)

| Concept | Field / header | Guest source | Logged-in source | Plugin version |
|---------|----------------|--------------|------------------|----------------|
| WP REST nonce | `X-WP-Nonce` | `GET /auth/nonce`.nonce | login / `GET /auth/me` | 1.0.0+ |
| WC cart nonce | `X-WC-Nonce` | `GET /auth/nonce`.cart_nonce` and/or `GET /cart`.cart_nonce | login / `GET /auth/me` `cart_nonce` | **1.0.2+** |
| Session | WC cookies | Required (primary) | Required | 1.0.0+ |
| Cart token | `X-StoreFuse-Cart-Token` | Response header; signed restore **experimental** until [cart-token-restore.md](cart-token-restore.md) passes | Same | 1.0.1+ code; support TBD |

Related: [storefuse-flutter](https://github.com/ProgrammerNomad/storefuse-flutter) planning docs (no shippable app in this phase).
