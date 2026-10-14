# Admin refactor v2 - Acceptance Checklist & Release Gates

Comprehensive verification checklist that must be passed before merging and shipping the v2 admin refactor. Maps to documentation in [target-sidebar.md](target-sidebar.md), [IMPLEMENTATION-PLAN.md](IMPLEMENTATION-PLAN.md), and [../fields-registry.md](../fields-registry.md).

This is an admin UI refactor over existing settings. No database migration, backfill, or data conversion is part of the scope.

---

## 1. REST API & Data Contracts

- [ ] **No Key Changes**: Zero option keys added, renamed, or deleted from `storefuse_bridge_settings`.
- [ ] **Sanitizer Integrity**: `StoreFuse_Bridge_Admin::sanitize_settings()` accepts all registered keys regardless of `primary_client`.
- [ ] **`GET /settings`**: Response payload matches contract exactly; admin-only keys (`primary_client`, webhook secrets) remain unexposed.
- [ ] **`GET /homepage`**: Output schema untouched; hero, layout, and merchandising blocks match existing schema.
- [ ] **`GET /status`**: Output schema untouched; module status map and readiness flags match.
- [ ] **`settings.app.ui`**: `storefuse.app-ui.v1` schema untouched for Flutter consumption.

---

## 2. Client-Aware Sidebar Presentation

- [ ] **Fresh Install Default**: Fresh installations default to `primary_client = other`; Dashboard guidance does not imply that the Other client mode is invalid or unconfigured.
- [ ] **Flutter Mode (`primary_client = flutter`)**:
  - [ ] `App Layout` is visible in sidebar under APPEARANCE.
  - [ ] `Navigation` is hidden from sidebar.
  - [ ] `Webhooks (ISR)` is hidden from sidebar.
- [ ] **Next.js Mode (`primary_client = nextjs`)**:
  - [ ] `Navigation` is visible in sidebar under APPEARANCE.
  - [ ] `Webhooks (ISR)` is visible in sidebar under DEVELOPER.
  - [ ] `App Layout` is hidden from sidebar.
- [ ] **Other / Headless Mode (`primary_client = other`)**:
  - [ ] Same visibility as Next.js (web navigation and webhooks enabled; App Layout hidden).
- [ ] **Settings Preservation**: Switching client and saving any one settings page preserves values saved on all other pages, including App Layout fields while in Next.js mode.
- [ ] **Presentation Only**: Client mode changes sidebar visibility and explanatory UI only. It does not change settings sanitization, route registration, or API response shapes.

---

## 3. Navigation, Hidden Pages & Direct URLs

- [ ] **Hidden-page access matrix**: Test each page/mode combination: App Layout hidden in Next.js and Other; Navigation and Webhooks (ISR) hidden in Flutter. On WordPress 6.0 and every WordPress version in the CI matrix, including its highest version, a user with the existing StoreFuse settings capability can open each hidden page directly and receives the normal page (HTTP 200, with no WordPress access-denied message).
- [ ] **Unauthorized access stays blocked**: Repeat direct-page and save attempts with a user who lacks the existing settings capability. The page form and settings must not be exposed or saved; accept WordPress's normal access-denied response even if that response uses HTTP 200. Keep the existing `settings_capability()` check unchanged; do not weaken or bypass it to make hidden pages reachable.
- [ ] **Contextual Info Bar**: When a page is accessed directly while hidden by active client, an inline notice renders:
  > *"This page is hidden from your sidebar because your primary client is set to [Client]..."* with a working link to Setup.
- [ ] **Legacy Slug Temporary Redirects**: Each legacy URL responds with HTTP 302, a safe `Location` pointing to the matching new admin page, and no further old-page output:
  - [ ] `page=storefuse-bridge-general` → `page=storefuse-bridge-branding`
  - [ ] `page=storefuse-bridge-storefront` → `page=storefuse-bridge-setup`
  - [ ] `page=storefuse-bridge-advanced` → `page=storefuse-bridge-modules`
  - [ ] `page=storefuse-bridge-mobile-app` → `page=storefuse-bridge-app-layout`
- [ ] **Sidebar Group Presentation**: All 5 groups (OVERVIEW, STORE CONTENT, APPEARANCE, COMMERCE, DEVELOPER) render cleanly and do not obstruct keyboard navigation (Tab/Shift-Tab, arrow keys).

---

## 4. Forms, Hidden Flags & Security

- [ ] **Settings Group Nonce**: Each of the ten options.php forms includes `settings_fields( 'storefuse_bridge_settings_group' )`: Setup, Branding, Homepage, Social & Trust, App Layout, Checkout, Store Policies, API & CORS, Webhooks (ISR), and Modules.
- [ ] **Per-form settings preservation**: For each form below, seed `storefuse_bridge_settings` with distinct values for fields on that form and on other forms. Submit only this form's own fields and its normal helper flags. Verify the submitted fields are sanitized and updated and every unrelated key is byte-for-byte/value-for-value unchanged. Run each case with unrelated App Layout keys and module toggles populated so those hidden settings cannot be accidentally reset.

  | Form under test | Additional assertion |
  |---|---|
  | Setup | Existing branding, checkout, App Layout, and module values survive changing client/storefront fields. |
  | Branding | Existing policy, hero, App Layout, and module values survive branding save. |
  | Homepage | Existing client, branding, App Layout, and module values survive homepage save. |
  | Social & Trust | Existing homepage, checkout, App Layout, and module values survive social save. |
  | App Layout | Include `_sfb_save_app_ui=1`; unchecked App Layout checkboxes update correctly and unrelated module/other-page values survive. |
  | Checkout | Existing client, policies, App Layout, and module values survive checkout save. |
  | Store Policies | Existing branding, checkout, App Layout, and module values survive policy save. |
  | API & CORS | Existing client, App Layout, and module values survive API save. |
  | Webhooks (ISR) | Existing client, App Layout, and module values survive webhook save. |
  | Modules | Include `_sfb_save_modules=1`; unchecked module toggles update correctly and unrelated App Layout/other-page values survive. |

- [ ] **Capability Check**: WordPress menu registration and page access use `StoreFuse_Bridge_Admin::settings_capability()`; unauthorized users cannot render or save any StoreFuse settings page.
- [ ] **App Layout Save Flag**: Form in `page-app-layout.php` includes `<input type="hidden" name="storefuse_bridge_settings[_sfb_save_app_ui]" value="1" />`.
  - [ ] Test: Unchecking checkboxes (e.g., `app_ui_product_show_sku`) successfully persists unchecked state upon save.
- [ ] **Modules Save Flag**: Form in `page-modules.php` includes `<input type="hidden" name="storefuse_bridge_settings[_sfb_save_modules]" value="1" />`.
  - [ ] Test: Disabling a module successfully persists unchecked state upon save.
- [ ] **AJAX Nonce & Capability**: All admin-ajax actions verify `check_ajax_referer( 'sfb_admin_nonce', 'nonce' )` and capability.

---

## 5. Functional & Screen-Specific Tests

- [ ] **Dashboard v2**:
  - [ ] Primary client badge displays active mode with working "Change client" link.
  - [ ] Every readiness item has an applicability condition and one of the requirement labels Required, Optional, Recommended, or Informational, plus a live state of Ready, Needs attention, or Not applicable.
  - [ ] Only applicable Required items can block readiness. Optional, Recommended, and Informational items are not presented as blockers.
  - [ ] Products, payment, and shipping checks reflect whether the store is preparing a catalog, online checkout, and shippable products; privacy policy is not shown as a universal technical blocker.
  - [ ] Navigation checks apply only to browser storefronts using Bridge menu locations. CORS checks apply only to cross-origin browser frontends; they are not Flutter setup tasks and same-origin frontends do not require CORS.
  - [ ] `primary_client = other` is valid and does not itself produce an incomplete warning. Do not infer browser navigation/CORS requirements from `other` alone.
  - [ ] Cache flush tool operates from Dashboard: "Flush all" and individual groups (`products`, `settings`, etc.) work and update `#sfb-flush-group-result`.
  - [ ] Quick step links point to v2 slugs (`setup`, `branding`, `homepage`, `checkout`, `api`).
- [ ] **Setup v2**:
  - [ ] Saving `primary_client` shows standard save feedback and explains that the sidebar reflects the selected mode.
  - [ ] Storefront URL and password reset path save and persist.
- [ ] **Branding v2**:
  - [ ] Primary app color, support URL, and announcement bar settings save and persist.
  - [ ] Site identity section shows Customizer link.
- [ ] **Store Policies v2**:
  - [ ] Return policy days, free shipping threshold amount and label save and persist.
- [ ] **Homepage v2**:
  - [ ] For Flutter: Hero section is collapsed by default behind an information notice with a manual toggle; every existing field remains editable and saved values are preserved. App Preview block is visible where it loads.
  - [ ] For Next.js and Other: Full hero section form is displayed; App Preview is hidden or omitted.
  - [ ] Featured categories JSON builder saves and persists.
  - [ ] Best sellers and promo banner settings save and persist.
- [ ] **Webhooks (ISR) v2**:
  - [ ] Revalidation path and secret save and persist.
  - [ ] "Test webhook" button triggers AJAX and displays HTTP response code.
  - [ ] "Clear log" button clears log table.
- [ ] **Modules v2**:
  - [ ] Modules are displayed in 5 organized cards (Core, Catalog, Commerce, Customer, Content).
  - [ ] Always-on modules (`status`, `auth`) cannot be unchecked.
  - [ ] Impact warnings display for settings/cart/checkout/orders/account and identify the affected client flows.
  - [ ] Warning displays if `webhooks` module is disabled.
- [ ] **API & CORS v2**:
  - [ ] CORS toggle and allowed origins textarea save and persist.
  - [ ] API Base URL copy button functions.

---

## 6. UX & Accessibility (Section 7)

- [ ] **Page Titles**: All screens use consistent `StoreFuse - {Page Title}` in `<h1>`.
- [ ] **Save Feedback**: `settings_errors( 'storefuse_bridge_settings' )` renders success alert after saving on every form page.
- [ ] **Unsaved Changes Confirmation**: `beforeunload` warning triggers when navigating away from dirty form.
- [ ] **Input Associations**: Every input, select, and textarea has a valid `id` with a corresponding `<label for="...">`.
- [ ] **Visible Focus**: Clear focus outline present when tabbing through form inputs and buttons.
- [ ] **Color Contrast**: WCAG AA compliance across all admin typography and badge states.

---

## 7. Documentation Sign-Off

- [ ] All 13 page specifications created in `docs/admin/refactor-v2/pages/`.
- [ ] **Source-to-registry reconciliation completed before implementation**: Compare all current admin form controls and their submitted names against the settings registrations, default values, sanitizers, option patch behavior, and actual consumers. The registry maps each stored setting once to its source and v2 destination. Document generated key families (for example `module_{id}_enabled`), array controls (for example `app_ui_home_hide_sections[]`), and helper POST flags explicitly; helper flags are not stored settings.
- [ ] Every registry row is verified against live source for field name, type/default, sanitization, current page, v2 destination, and consumer. Every current settings control is classified as a registry setting or documented non-setting control; there are no unexplained source-only fields or stale registry rows.
- [ ] The source audit records the actual callbacks, slugs, capabilities, nonces, AJAX actions, JS/CSS selectors, and API output touched by the planned pages, and resolves discrepancies in the docs before code starts.
- [ ] Target sidebar document `target-sidebar.md` updated with technical mechanisms.
- [ ] Client mapping `client-mapping.md` aligned with Flutter reference app.
- [ ] No database migration or backfill is introduced; all existing option keys and stored values remain in use.
- [ ] Extensions page describes the existing hooks and companion-module filter and does not claim an extension registry exists.

---

**Release Gate Approval:**

Tested by: _______________________ Date: _______________ Pass: [ ]
Approved by: _____________________ Date: _______________ Pass: [ ]
