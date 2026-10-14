# StoreFuse Bridge admin refactor v2

**Status:** Proposal for review. This document describes a planned wp-admin refactor; it is not a code change.

## Goal

Reorganize the StoreFuse Bridge administration screens around store-owner tasks, make the sidebar relevant to the configured primary client, and improve page-level guidance and accessibility. Keep the existing settings, sanitization, permissions, and client-facing API behavior intact.

See the [target sidebar](target-sidebar.md), [page specifications](pages/README.md), [field registry](../fields-registry.md), and [acceptance checklist](refactor-acceptance.md) for the detailed design.

## Scope and constraints

### Included

- Reorganize the existing StoreFuse menu into 13 focused screens.
- Split the current General and Storefront & Clients screens into smaller screens.
- Add client-aware sidebar visibility and explanatory notices on directly opened hidden pages.
- Move the existing cache controls from API & Tools to Dashboard.
- Preserve the existing settings option, setting keys, save behavior, capability checks, nonces, and REST contracts.
- Improve page guidance, save feedback, keyboard operation, labels, focus, and contrast.

### Out of scope

- **No database migration plan is required or included.** This refactor reuses the existing `storefuse_bridge_settings` WordPress option and its current keys. It adds no tables, schema, backfill, or data conversion.
- No settings key or REST field is added, renamed, or removed as part of this UI refactor.
- No REST route, response schema, authentication flow, Flutter behavior, or Next.js behavior is redesigned here.
- No new extension registry or module-registration API is introduced. Preserve and accurately document the existing `storefuse_bridge_modules` runtime filter.
- No payment gateway SDK or gateway compatibility guarantee is added.

The refactor must not reset or delete saved settings. Splitting a form into multiple screens still saves a patch to the same option and must preserve keys that are not part of that form.

## Current problems

1. The current sidebar exposes mobile-app controls to web-only stores and web-navigation/Webhook controls to Flutter-only stores.
2. `primary_client` is buried on Storefront & Clients rather than presented as an early setup choice.
3. General mixes branding and announcement settings with store policies; Storefront & Clients mixes connection details with ISR webhook settings; API & Tools combines CORS with cache operations.
4. Advanced presents module toggles as a flat list, despite the different impact of those modules.
5. The current screens provide too little context about which settings affect the Flutter app, a browser storefront, or both.

## Core behavior rules

1. **Menu visibility is not authorization.** All pages keep the existing `manage_woocommerce` capability (or the existing capability filter). Hiding a menu entry must not grant or remove access.
2. **Client mode controls presentation.** `primary_client` selects sidebar visibility and contextual UI guidance. It must not gate settings saves, REST routes, API responses, or sanitization.
3. **Keep settings intact.** Changing client mode or saving one page must preserve settings configured on other pages, including hidden pages.
4. **Keep contracts stable.** Reuse existing option keys and API response shapes. The `primary_client` setting and webhook secret remain admin-only.
5. **Direct links remain usable.** A page hidden from the current sidebar remains registered for authorized direct access and shows a brief explanation with a link to Setup.
6. **Old admin URLs have a clear destination.** Keep the four legacy slugs working through redirects; the old General destination must also link to the new Store Policies page because General is split across two screens.
7. **No database migration.** This is a wp-admin presentation refactor over the existing option data.

## Target information architecture

The five names below are logical groups for ordering and visual separation. WordPress renders a plugin submenu as a flat list; do not present these as native collapsible menu groups. See [target-sidebar.md](target-sidebar.md) for the visibility matrix and menu details.

| Group | Pages |
|---|---|
| Overview | Dashboard, Setup |
| Store Content | Homepage, Social & Trust |
| Appearance | Branding, App Layout, Navigation |
| Commerce | Checkout, Store Policies |
| Developer | API & CORS, Webhooks (ISR), Modules, Extensions |

There are 13 screens. `App Layout` is shown in the sidebar for Flutter; `Navigation` and `Webhooks (ISR)` are shown for Next.js and Other. All three pages remain directly accessible to authorized users.

## Implementation sequence

### 0. Complete the source audit before implementation

This is a required documentation gate. Do not begin the PHP, JavaScript, CSS, or view refactor until the audit is complete and the docs match the current source. Inspect at least:

- `includes/class-admin.php` and every currently registered admin callback and menu slug.
- Every current `admin/views/page-*.php` view, including the fields and helper inputs each form submits.
- `includes/class-settings.php`, `includes/class-app-ui.php`, all registered settings, defaults, sanitizers, and the `storefuse_bridge_settings` option update path.
- Admin JavaScript and CSS selectors/actions used by media pickers, builders, previews, diagnostics, cache controls, and AJAX tools.
- Every admin AJAX handler, its nonce and capability checks, and the actual response behavior.
- The current REST output for settings, homepage, status, checkout, and navigation, so the UI plan does not imply a contract change.

Reconcile the source with [the field registry](../fields-registry.md) and all 13 page specifications. Every existing form control that reads or writes a setting must map to exactly one registry row and one target page. Every registry row must be verified against its source field, default, sanitizer, and consumer. Explicitly account for checkbox/helper POST fields such as `_sfb_save_app_ui` and `_sfb_save_modules`; they are not stored option keys. Record source-only fields, stale registry entries, aliases, and controls that are not settings fields, then correct the docs before coding.

The audit deliverable is a file-level implementation map covering each page, field set, callback, handler, selector, capability/nonces, and any unresolved behavior. Update the docs and acceptance checklist for every discrepancy. No database migration, backfill, or data conversion is part of this gate or the refactor.

### 1. Confirm the existing contracts

Use the live plugin and [current admin documentation](../current/) as the baseline. Record the current menu slugs, callbacks, settings group, option key, hidden save flags, AJAX actions, capability checks, and API output. The source audit above must resolve the field registry against the live code before it is treated as authoritative.

### 2. Define the menu and client-visibility map

Centralize the planned menu metadata (slug, label, logical group, visibility, callback, and view). Keep the `storefuse-bridge` top-level slug and capability. Register hidden pages so authorized direct URLs still resolve. Test all three hidden pages with an authorized user and a user lacking the existing capability on WordPress 6.0 and every WordPress version in the CI matrix, including its highest version, before removing old menu entries. Do not weaken, replace, or bypass the capability check to make a hidden page reachable. Add a shared contextual notice for pages hidden by `primary_client`.

Use a flat submenu ordered by the five logical groups. Separate clusters visually without adding fake links or keyboard stops. If visible group labels are implemented, they must be real, non-focusable text with suitable semantics and an accessible fallback; CSS pseudo-content alone is not an accessible heading.

### 3. Split the existing settings screens

Create Setup, Branding, Store Policies, and Webhooks (ISR) views from the current General and Storefront & Clients screens. Move existing fields only; do not rename keys, add storage, or remove values. Keep WordPress Customizer and WooCommerce settings linked as native configuration rather than copying their data into Bridge.

### 4. Update existing screens

- **Dashboard:** add a client badge, setup guidance, existing cache controls, API diagnostics, and module summary.
- **Homepage:** retain all saved hero fields for every client. The client mode may change the default disclosure and supporting explanation, but must not hide fields from saving or discard values.
- **Social & Trust:** retain existing social links and trust-badge builder.
- **App Layout:** rename the screen and retain `_sfb_save_app_ui` in its form.
- **Navigation:** keep WordPress menu assignment and preview behavior; add the Flutter context notice.
- **Checkout:** explain the existing redirect and headless flows accurately; do not promise gateway support.
- **API & CORS:** keep CORS controls and diagnostics; move existing cache controls to Dashboard.
- **Modules:** group existing toggleable modules and retain `_sfb_save_modules`.
- **Extensions:** document existing hooks and companion-plugin capabilities; do not imply a registry exists.

### 5. Preserve forms, security, and legacy navigation

Every options.php form uses `settings_fields( 'storefuse_bridge_settings_group' )`, renders the existing settings errors, and submits only its own fields. The ten settings forms are Setup, Branding, Homepage, Social & Trust, App Layout, Checkout, Store Policies, API & CORS, Webhooks (ISR), and Modules. Dashboard, Navigation, and Extensions are not settings-option forms.

Add a regression test for each of the ten forms: seed the full existing option array, submit only that form's fields and normal helper flags, then verify its submitted values are sanitized and updated while every unrelated key retains its exact prior value. Include App Layout and Modules saves with `_sfb_save_app_ui` and `_sfb_save_modules`, and verify unrelated App Layout/module settings survive saves from the other eight forms. These tests prove the split forms patch the existing option rather than resetting settings on hidden or other pages.

Keep the existing App Layout and Modules hidden batch flags. Preserve capability checks, WordPress nonces, AJAX nonces and capability checks, and escaping/sanitization. Register legacy-slug redirects:

| Old slug | Destination |
|---|---|
| `storefuse-bridge-general` | Branding, with a visible link to Store Policies |
| `storefuse-bridge-storefront` | Setup |
| `storefuse-bridge-advanced` | Modules |
| `storefuse-bridge-mobile-app` | App Layout |

Each legacy slug redirects with a temporary `302` through `wp_safe_redirect()` and exits after the redirect. Do not use a permanent `301` for these admin navigation aliases.

### 6. Verify against acceptance criteria

Complete every applicable item in [refactor-acceptance.md](refactor-acceptance.md), including direct-link access, save preservation, API contract comparisons, all ten forms, AJAX security, responsive layout, and keyboard/screen-reader checks. This documentation review does not authorize or include the implementation itself.

## PHP change inventory for the later code phase

Expected areas include `storefuse-bridge/includes/class-admin.php`, existing admin view files, new split view files, and the current admin CSS/JavaScript where needed. Exact filenames and whether existing views are renamed or replaced should be confirmed against the live source at implementation time. No database migration or backfill file is expected.

## Decisions for review

- Approve the five logical sidebar groups and the Flutter / Next.js / Other visibility matrix.
- Confirm that client mode may tailor explanatory UI and default disclosure while never removing settings or changing API behavior.
- Confirm the Extensions screen remains a documentation/diagnostic page for existing hooks rather than a plugin registry.
- Confirm this documentation is ready to use as the implementation specification.
