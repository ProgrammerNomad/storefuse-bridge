# Admin → client mapping

Which wp-admin screens affect **Flutter** vs **Next.js / other** storefronts. API remains the same for all clients unless modules are disabled.

## Primary client (`primary_client`)

Admin-only setting - **not exposed in REST**. v2 uses it for sidebar visibility and contextual admin guidance. It does not gate API routes, settings, or responses.

## Flutter (reference app)

| App surface | WordPress admin | API |
|-------------|-----------------|-----|
| Theme / accent | General → primary colour; Mobile app → secondary colour, theme mode, font | `settings.app.primary_color`, `app.ui.theme` |
| Bottom tabs | **Mobile app → Active tabs & order** (`app_ui_nav_tabs`) | `app.ui.navigation.tabs` |
| Tab availability | Modules (disable cart, wishlist, etc.) | `GET /status` → `modules` |
| Announcement | General → announcement bar | `settings.header`, homepage |
| Home content blocks | Homepage (sections) + Mobile app → section order/hide | `GET /homepage` + `app.ui.screens.home` + `homepage.layout` |
| Hero on home | Homepage hero (API still returns data); v2 collapses the hero editor by default for Flutter while keeping its fields available | `homepage.hero` - app visibility can be controlled via `hide_sections` |
| Shop / search / category UI | Mobile app → catalog + search | `app.ui.catalog`, `app.ui.screens.shop`, `screens.search` |
| Product cards stock badge | Mobile app → catalog stock badge | `app.ui.catalog.show_stock_badge` |
| Product card rating/sale | **Not in wp-admin** | Flutter app defaults |
| PDP | Mobile app → product options | `app.ui.screens.product` |
| Cart | Mobile app → cart options + General free shipping | Cart API + `app.ui.screens.cart` + `settings.store` |
| Checkout mode | Checkout → redirect/headless | `GET /checkout/config` |
| Checkout guest banner | Mobile app | `app.ui.screens.checkout` |
| Account / orders / wishlist | Modules | Module routes |
| Legal URLs | WP privacy page + Checkout terms | `settings.app` URLs |
| Navigation drawer (WP menus) | Navigation page | Optional if app reads `settings.navigation` - bottom tabs use `app_ui_nav_tabs` |

## Next.js / web storefront

| Surface | Admin |
|---------|-------|
| Header/footer nav | Navigation → `storefuse-header`, `storefuse-footer` |
| Homepage hero | Homepage → hero section |
| CORS | API & Tools |
| ISR cache purge | Storefront / Webhooks (v2) + `module_webhooks` |
| Storefront URL | Setup (v2) / Storefront today |
| Checkout | Checkout page |
| Merchandising | Homepage sections |
| Branding | General / Branding (v2) |

## Homepage section visibility (v2 settled)

| Section | Flutter admin form | Web admin form |
|---------|-------------------|----------------|
| Featured categories | Homepage | Homepage |
| Best sellers / new / promo | Homepage | Homepage |
| Hero | Hidden (info → App Layout) | Homepage |
| App preview widget | Homepage `<details>` | Hidden |

Section IDs: see [mobile-app.md](current/pages/mobile-app.md).

## Corrections vs older client docs

- Bottom navigation order is **`app_ui_nav_tabs`**, not Advanced module toggles alone (modules can remove features; tabs string defines order/which tabs appear).
