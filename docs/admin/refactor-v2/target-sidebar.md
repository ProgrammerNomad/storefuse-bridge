# Target sidebar and page access (admin refactor v2)

This is the planned menu order and visibility behavior. It does not change the settings or API contract. The five group names are logical categories; WordPress displays the StoreFuse submenu as one flat list.

## Behavior rules

1. `primary_client` has three values: `flutter`, `nextjs`, and `other` (the existing default).
2. It controls which client-specific pages appear in the sidebar and the contextual guidance shown inside pages. It does not disable routes, reject settings, or change API responses.
3. Hidden pages stay registered and remain directly accessible to users with the existing StoreFuse settings capability.
4. Opening a hidden page directly shows a small notice explaining the active client mode and linking to Setup.
5. Existing option keys and saved values are retained.
6. Legacy slugs redirect to their new destination; the old General destination also links to Store Policies.

## Menu order and client visibility

### `primary_client = flutter`

```text
StoreFuse
  Dashboard
  Setup
  ─ Store Content ─
  Homepage
  Social & Trust
  ─ Appearance ─
  Branding
  App Layout
  ─ Commerce ─
  Checkout
  Store Policies
  ─ Developer ─
  API & CORS
  Modules
  Extensions
```

Navigation and Webhooks (ISR) remain available by direct URL but are not shown in this sidebar mode.

### `primary_client = nextjs` or `other`

```text
StoreFuse
  Dashboard
  Setup
  ─ Store Content ─
  Homepage
  Social & Trust
  ─ Appearance ─
  Branding
  Navigation
  ─ Commerce ─
  Checkout
  Store Policies
  ─ Developer ─
  API & CORS
  Webhooks (ISR)
  Modules
  Extensions
```

App Layout remains available by direct URL but is not shown in these sidebar modes.

## Page matrix

| Page | Planned slug | Group | Flutter | Next.js | Other | If hidden and opened directly |
|---|---|---|---:|---:|---:|---|
| Dashboard | `storefuse-bridge` | Overview | Show | Show | Show | Normal page |
| Setup | `storefuse-bridge-setup` | Overview | Show | Show | Show | Normal page |
| Homepage | `storefuse-bridge-homepage` | Store Content | Show | Show | Show | Normal page; client-aware guidance only |
| Social & Trust | `storefuse-bridge-social` | Store Content | Show | Show | Show | Normal page |
| Branding | `storefuse-bridge-branding` | Appearance | Show | Show | Show | Normal page |
| App Layout | `storefuse-bridge-app-layout` | Appearance | Show | Hide | Hide | Render page with client context notice |
| Navigation | `storefuse-bridge-navigation` | Appearance | Hide | Show | Show | Render page with client context notice |
| Checkout | `storefuse-bridge-checkout` | Commerce | Show | Show | Show | Normal page |
| Store Policies | `storefuse-bridge-policies` | Commerce | Show | Show | Show | Normal page |
| API & CORS | `storefuse-bridge-api` | Developer | Show | Show | Show | Normal page |
| Webhooks (ISR) | `storefuse-bridge-webhooks` | Developer | Hide | Show | Show | Render page with client context notice |
| Modules | `storefuse-bridge-modules` | Developer | Show | Show | Show | Normal page |
| Extensions | `storefuse-bridge-extensions` | Developer | Show | Show | Show | Normal page |

## WordPress menu implementation notes

- Keep the existing `storefuse-bridge` top-level menu slug and capability.
- Register visible child pages under the StoreFuse parent. Register hidden child pages using the WordPress admin page mechanism that supports direct access without displaying a submenu entry. Before shipping, test App Layout hidden in both Next.js and Other modes, and Navigation plus Webhooks (ISR) hidden in Flutter mode. On WordPress 6.0 and every WordPress version in the CI matrix (including its highest version), confirm a user with the existing settings capability can open each hidden page directly and a user without that capability remains blocked. Keep the existing capability check intact; never weaken or bypass it to make hidden pages reachable.
- Keep the submenu flat and in the order shown above. Use subtle dividers to distinguish logical clusters. Do not create dummy links or keyboard stops for group labels.
- If the UI uses actual group labels, render them as non-interactive text with accessible semantics, and ensure they do not interfere with WordPress menu keyboard behavior. Do not rely on CSS generated content as the only label.
- The menu capability and each page callback keep using `StoreFuse_Bridge_Admin::settings_capability()`; its default is `manage_woocommerce` and remains filterable.
- A hidden-page notice must be informative only. It must not block the form, disable settings, or imply the API ignores those values.

## Legacy slugs

| Existing slug | New destination | Additional behavior |
|---|---|---|
| `storefuse-bridge-general` | `storefuse-bridge-branding` | Branding page links prominently to Store Policies, since the old screen held both kinds of settings |
| `storefuse-bridge-storefront` | `storefuse-bridge-setup` | Setup retains the existing client and storefront fields |
| `storefuse-bridge-advanced` | `storefuse-bridge-modules` | Existing module toggles remain available |
| `storefuse-bridge-mobile-app` | `storefuse-bridge-app-layout` | Direct destination remains usable even when hidden from the sidebar |

Use a temporary `302` response via `wp_safe_redirect()` to the new admin URL, then stop rendering the old page. These are wp-admin navigation aliases, not public URLs; a permanent redirect has no SEO benefit and can be cached by browsers during ongoing admin work. Keep the legacy routes registered so bookmarks and plugin links do not fail.

## Homepage presentation by client

| Homepage section | Flutter mode | Next.js / Other mode |
|---|---|---|
| Hero settings | Available in a collapsed-by-default section with an explanation that the Flutter app uses native layout controls; values remain editable and saved | Expanded/prominent by default |
| Featured categories and product feeds | Available | Available |
| Promo banner | Available | Available |
| App preview | Shown where the existing preview can load | Hidden or omitted |

Client mode may change initial disclosure and explanatory text only. It does not remove fields from the form or clear their values.

## Sign-off

Use the [acceptance checklist](refactor-acceptance.md) to verify the menu, direct URLs, client context, legacy redirects, and keyboard operation before implementation is considered complete.
