# StoreFuse Bridge

**Headless WooCommerce REST API** for Next.js, Flutter, and any web or mobile client.

StoreFuse Bridge exposes a single versioned namespace - `/wp-json/storefuse/v1/*` - with storefront-shaped JSON (products, cart, checkout, account, orders, settings). Clients never need WooCommerce consumer keys or raw `wc/v3` responses.

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

---

## Quick start

1. Install and activate **StoreFuse Bridge** on your WordPress store.
2. Open **StoreFuse → Advanced** to set module toggles, storefront URL, and ISR webhook secret.
3. Point your client at `{WOO_URL}/wp-json/storefuse/v1`.
4. Call `GET /status` to confirm version and enabled modules.

---

## API overview

| Area | Examples |
|------|----------|
| Health | `GET /status` |
| Store config | `GET /settings`, `/navigation`, `/homepage` |
| Catalog | `GET /products`, `/categories`, `/search` |
| Session | `GET /cart`, `POST /cart/add` (see nonce headers below) |
| Auth | `GET /auth/nonce`, `POST /auth/login`, `GET /auth/me` |
| Customer | `GET /orders`, `/account`, `/wishlist` (auth cookie) |
| Checkout | `GET /checkout/config`, `POST /checkout`, `POST /checkout/redirect-url` |

**Full route matrix (audited from PHP):** [docs/verified-routes.md](docs/verified-routes.md)  
**Payload reference:** [docs/api-reference.md](docs/api-reference.md)

### Auth at a glance

- **Public catalog** - no credentials.
- **Cart/checkout writes** - WooCommerce session cookie + `X-WC-Nonce`.
- **Login/register/logout** - `GET /auth/nonce` then `X-WP-Nonce` on POST.
- **Account/order mutations** - WordPress auth cookie + `X-WP-Nonce`.

Do not cache credentialed responses on shared CDNs.

---

## Client guides

| Client | Guide |
|--------|--------|
| Next.js / StoreFuse web | [docs/clients/nextjs.md](docs/clients/nextjs.md) |
| Flutter / mobile | [docs/clients/mobile-flutter.md](docs/clients/mobile-flutter.md) |
| Other SPAs | [docs/clients/generic-web.md](docs/clients/generic-web.md) |

---

## Extensions

Companion WordPress plugins can extend responses via `storefuse_bridge_*` filters without forking Bridge. See [docs/extensions.md](docs/extensions.md). Formal module registration is specified for v0.2 in [docs/extension-api-v0.2.md](docs/extension-api-v0.2.md) (not implemented in 0.1.0).

---

## Development

- [PLAN.md](PLAN.md) - roadmap and principles  
- [docs/architecture.md](docs/architecture.md) - module layout and caching  
- [docs/README.md](docs/README.md) - documentation index  

---

## License

GPL-2.0-or-later
