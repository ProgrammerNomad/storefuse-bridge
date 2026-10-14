# Dashboard

## Purpose

Operations overview for shop managers: setup readiness, API base URL, module status, WC feature flags, cache flush, documentation links.

## Menu

| | |
|--|--|
| **Current slug** | `storefuse-bridge` |
| **View** | `admin/views/page-dashboard.php` |
| **v2 slug** | `storefuse-bridge` (unchanged) |
| **Saves settings** | No |

## v2 sidebar visibility

Visible for all `primary_client` values.

## Page layout (sections)

1. **H1** - StoreFuse Bridge + version badge
2. **Setup steps nav** - Buttons: Connect app (storefront), Store identity (general), Merchandising (homepage), Checkout, Diagnostics (api)
3. **Setup readiness** - Table of OK/Todo items (see non-settings UI)
4. **API base URL** - Copy + Test `/status`
5. **Modules** - Read-only table; link to Advanced
6. **Features** - headless_checkout, HPOS, store_api from WC compat
7. **Cache** - Flush all + link to API & Tools
8. **Documentation** - GitHub docs, staging smoke
9. **Settings quick links** - General, Homepage, Storefront, API

## Fields

None. No `options.php` form on this page.

## Non-settings UI

| Item | Behaviour |
|------|-----------|
| Readiness: products published | Link to products list |
| Payment method enabled | WC → Payments |
| Delivery zones | WC → Shipping |
| Privacy policy | options-privacy.php |
| Header/footer menu assigned | nav-menus.php |
| Storefront URL configured | storefront page |
| HTTPS recommended | informational |
| CORS origins when external storefront | api page |
| Primary client selected | storefront page; OK when not `other` |

## JavaScript

| Control | Action |
|---------|--------|
| `#sfb-flush-cache` | AJAX flush group `all` (confirm dialog) |
| `.sfb-copy-btn` | Clipboard copy API URL |

## v2 deltas

- Client badge + link to Setup
- Treat `other` as a valid client mode; do not mark it incomplete just because it is the default
- Conditional readiness rows (hide nav/CORS items for Flutter where appropriate)
- Show incomplete setup guidance based on missing configuration, not on the client mode alone
- Move **targeted cache groups** from API & Tools here (full cache section)
- Update quick links to new page names (Branding, Setup, Modules)

See [../../refactor-v2/target-sidebar.md](../../refactor-v2/target-sidebar.md).
