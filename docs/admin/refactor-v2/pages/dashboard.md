# Dashboard (v2 - planned)

## Purpose

The central overview and operational hub for StoreFuse Bridge. Displays primary client status, quick setup steps, conditional store readiness checks, API diagnostics, and cache management tools relocated from the API page.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge` |
| **Menu Title** | `Dashboard` |
| **Parent** | `storefuse-bridge` (Main menu item) |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

All clients (`flutter`, `nextjs`, `other`). Always visible as the top item under the `OVERVIEW` group.

---

## Planned Sections & Layout

### 1. Header & Primary Client Badge
- Title: `StoreFuse Bridge v{version}`
- **Primary Client Badge**: Indicates whether the store is set up for **Flutter App**, **Next.js Storefront**, or **Other / Headless**.
  - Displays a pill badge with the active client mode.
  - Action link: `Change client →` pointing to `admin.php?page=storefuse-bridge-setup`.
- **Setup guidance**: Link to Setup and show incomplete connection steps based on actual saved values. `other` is a valid client mode and must not be treated as an error or an unselected value.

### 2. Setup Steps Bar
Refreshed navigation pills linking to v2 pages:
- `setup` - *Connect App & Client*
- `branding` - *Store Identity & Colors*
- `homepage` - *Merchandising & Preview*
- `checkout` - *Checkout Flow*
- `api` - *API & CORS*

### 3. Conditional Setup Readiness Checklist
Show only checks that apply to the store's products and configured frontend. `primary_client` may guide the explanation, but it is not enough by itself to decide that a check is required: `other` is a supported mode, and an Other integration may be a browser storefront, a native app, or another client. Do not show a required item as incomplete when its condition does not apply.

Each row has a requirement label (`Required`, `Optional`, `Recommended`, or `Informational`) and a live state (`Ready`, `Needs attention`, or `Not applicable`). Only applicable required rows can block a “ready to launch” summary. Optional, recommended, and informational rows must not be presented as launch blockers.

| Readiness Item | Requirement and applicability | When applicable but incomplete | Target Link |
|---|---|---|---|
| Published products | Required before launching a product catalog; otherwise Informational | Needs attention | `edit.php?post_type=product` |
| Payment method | Required only when customers can place online orders through checkout | Needs attention | `admin.php?page=wc-settings&tab=checkout` |
| Shipping zones | Required only when the store sells products that need shipping; otherwise Not applicable | Needs attention | `admin.php?page=wc-settings&tab=shipping` |
| Privacy policy | Recommended; show as informational if no policy is configured. Legal obligations vary by store and jurisdiction | Informational or Needs attention, never a universal technical blocker | `options-privacy.php` |
| Storefront URL | Required only when the outgoing Webhooks module is enabled; otherwise Optional | Needs attention only when webhooks are enabled | `admin.php?page=storefuse-bridge-setup` |
| HTTPS | Recommended for production storefronts and APIs | Recommended | - |
| Header/Footer menu assignment | Required only for a browser storefront using the Bridge header/footer menu locations; Not applicable to Flutter-only app navigation | Needs attention only for a browser storefront that uses those locations | `nav-menus.php` |
| CORS origins | Required only for a cross-origin browser frontend with CORS enabled; same-origin browser frontends and native Flutter API requests do not need browser CORS | Needs attention only for a cross-origin browser frontend | `admin.php?page=storefuse-bridge-api` |

For `primary_client = other`, do not infer that navigation or CORS is required from the mode alone. Use known configured frontend behavior where the plugin can determine it; otherwise present the check as Optional or Informational and explain the condition. A valid `other` selection must never create an incomplete-client warning by itself.

### 4. Cache Management (Relocated from API & Tools)
Moved from `page-api.php` into Dashboard to keep operational controls in one place:
- **Last Flush Timestamp**: Displays time and flushed group from `storefuse_bridge_last_flush_at` option.
- **Granular Group Flush Buttons**:
  - `Flush All` (`group: all`)
  - `Products` (`group: products`)
  - `Settings` (`group: settings`)
  - `Navigation` (`group: navigation`)
  - `Homepage` (`group: homepage`)
  - `Search` (`group: search`)
- Feedback container: `#sfb-flush-group-result`.
- Handled via existing AJAX action `storefuse_bridge_flush_cache_group`.

### 5. API Base & Status Card
- Read-only API base URL: `get_site_url() . '/wp-json/storefuse/v1'`
- Copy URL button (`.sfb-copy-btn` + `data-target="sfb-api-url"`)
- `Test /status` button (opens `/wp-json/storefuse/v1/status` in new tab)
- Link to GitHub / online documentation.

### 6. Module Status Summary
- Compact summary table reflecting currently active vs disabled modules (matches `GET /status`).
- Direct link to `admin.php?page=storefuse-bridge-modules`.

---

## Technical Notes

- **Non-form page**: No `<form method="post" action="options.php">`.
- **AJAX actions used**:
  - `storefuse_bridge_flush_cache_group`
  - `storefuse_bridge_dismiss_setup`
- **Security**: Must verify `current_user_can( StoreFuse_Bridge_Admin::settings_capability() )`.

The Dashboard is not an options form. It must not present `primary_client = other` as incomplete setup by itself; the setting is the supported default and a valid selection.
