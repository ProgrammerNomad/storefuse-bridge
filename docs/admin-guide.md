# StoreFuse Bridge - WordPress admin guide

Merchants configure headless storefront content under **WooCommerce → StoreFuse**. Developers trace API fields using this registry.

**Documentation in wp-admin:** All “Documentation” links in the plugin admin open the [GitHub docs index](https://github.com/ProgrammerNomad/storefuse-bridge/blob/main/docs/README.md) (single source of truth).

**Permissions:** `manage_woocommerce` (shop managers).

## Admin pages

| Menu | Slug | Purpose |
|------|------|---------|
| Dashboard | `storefuse-bridge` | Readiness, modules, features, docs |
| General | `storefuse-bridge-general` | Announcement, policies, site identity links |
| Homepage | `storefuse-bridge-homepage` | Hero, featured categories, product sections |
| Navigation | `storefuse-bridge-navigation` | Menu locations, preview, nav cache flush |
| Social & Trust | `storefuse-bridge-social` | Social URLs, trust badges |
| Checkout | `storefuse-bridge-checkout` | Redirect vs headless mode |
| Storefront & Clients | `storefuse-bridge-storefront` | Storefront URL, reset path, webhooks |
| API & Tools | `storefuse-bridge-api` | CORS, cache groups, API links |
| Advanced | `storefuse-bridge-advanced` | Module toggles |
| Extensions | `storefuse-bridge-extensions` | Hooks & companion plugin docs |

## Settings field registry

Option key: `storefuse_bridge_settings` (single serialized array).

| Setting key | Admin page | API path |
|-------------|------------|----------|
| `announcement_bar_*` | General | `GET /settings` → `header`; mirrored on `GET /homepage` → `announcement_bar` |
| `return_policy_days`, `free_shipping_*` | General | `GET /settings` → `store` |
| (logo, favicon, name) | General → Customizer | `GET /settings` → `site` |
| `hero_*` | Homepage | `GET /homepage` → `hero` |
| `featured_categories` | Homepage | `GET /homepage` → `featured_categories` |
| `homepage_best_sellers_*` | Homepage | `GET /homepage` → `best_sellers` |
| `homepage_new_arrivals_*` | Homepage | `GET /homepage` → `new_arrivals` |
| `homepage_promo_banner_*` | Homepage | `GET /homepage` → `promo_banner` |
| Nav menus (WP locations) | Navigation | `GET /navigation`, `GET /settings` → `navigation` |
| `social_*` | Social & Trust | `GET /settings` → `social_links` |
| `trust_badges` | Social & Trust | `GET /settings` → `trust_badges`; `GET /homepage` → `trust_items` |
| `checkout_mode`, `checkout_*` | Checkout | `GET /checkout/config`; `GET /status` → `features.headless_checkout` |
| `storefront_url`, `storefront_reset_path` | Storefront | Password reset filter; not in public settings JSON |
| `storefront_revalidate_path`, `revalidation_secret` | Storefront | Outgoing webhooks only |
| `module_*_enabled` | Advanced | `GET /status` → `modules` |
| `cors_enabled`, `cors_allowed_origins` | API & Tools | HTTP headers on `/storefuse/v1/*` |
| `primary_client` | Storefront | Admin guidance only (not in API) |

## WooCommerce-native (not in Bridge settings)

- Payment gateways → **WooCommerce → Settings → Payments**
- Products, orders, coupons → native WooCommerce screens

## Operational notes

- Saving any settings page flushes all Bridge transients (existing behaviour).
- **API & Tools** and **Navigation** support targeted cache flush groups via AJAX.
- Last manual flush time is stored in `storefuse_bridge_last_flush_at`.

See also [verified-routes.md](verified-routes.md) and [architecture.md](architecture.md).

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Browser client blocked by CORS | Origin not allowlisted | **API & Tools → CORS**; add exact scheme+host+port; confirm preflight exposes `X-WC-Nonce`, `X-StoreFuse-Cart-Token` |
| Cart writes return `invalid_nonce` | Missing or stale `X-WC-Nonce` | Call `GET /cart` first; send nonce header on POST/PUT/DELETE |
| Auth writes fail 403 | Missing `X-WP-Nonce` | `GET /auth/nonce` before login/register; refresh after login |
| Stale catalog after admin edit | Transient cache | Save settings (auto flush) or **API & Tools → Flush** product/settings group |
| Session/cart lost on mobile | Cookie not persisted | Use cookie jar + cart token header; see [auth-strategy.md](auth-strategy.md) |
| Orders missing in app | HPOS or wrong customer | Confirm user logged in; Bridge uses WC order APIs (HPOS-compatible) |
| Webhook never hits storefront | SSRF guard or bad URL | **Storefront URL** must be HTTPS public host; see [security.md](security.md) |

Staging verification: [staging-smoke.md](staging-smoke.md). Production gates: [acceptance-gates.md](acceptance-gates.md).
