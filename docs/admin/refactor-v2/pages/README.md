# Refactor v2 - Page Specifications

Complete page-by-page specifications for all 13 admin screens in the v2 architecture. Fields reuse existing option keys without breaking API contracts - see [../../fields-registry.md](../../fields-registry.md).

## Page Index by Group

| Group | Page | Slug | Status in v2 | Spec Document |
|---|---|---|---|---|
| **OVERVIEW** | Dashboard | `storefuse-bridge` | Modified (Cache controls & client badge) | [dashboard.md](dashboard.md) |
| **OVERVIEW** | Setup | `storefuse-bridge-setup` | **New** (Split from Storefront) | [setup.md](setup.md) |
| **STORE CONTENT** | Homepage | `storefuse-bridge-homepage` | Modified (Contextual hero per client) | [homepage.md](homepage.md) |
| **STORE CONTENT** | Social & Trust | `storefuse-bridge-social` | Form layout & UX polished | [social-trust.md](social-trust.md) |
| **APPEARANCE** | Branding | `storefuse-bridge-branding` | **New** (Split from General) | [branding.md](branding.md) |
| **APPEARANCE** | App Layout | `storefuse-bridge-app-layout` | Renamed from Mobile app; Flutter-only in sidebar | [app-layout.md](app-layout.md) |
| **APPEARANCE** | Navigation | `storefuse-bridge-navigation` | Web-only in sidebar; Flutter direct URL notice | [navigation.md](navigation.md) |
| **COMMERCE** | Checkout | `storefuse-bridge-checkout` | Contextual notes on redirect vs headless | [checkout.md](checkout.md) |
| **COMMERCE** | Store Policies | `storefuse-bridge-policies` | **New** (Split from General) | [store-policies.md](store-policies.md) |
| **DEVELOPER** | API & CORS | `storefuse-bridge-api` | Renamed & focused (Cache moved to Dashboard) | [api-cors.md](api-cors.md) |
| **DEVELOPER** | Webhooks (ISR) | `storefuse-bridge-webhooks` | **New** (Split from Storefront; Web-only sidebar) | [webhooks-isr.md](webhooks-isr.md) |
| **DEVELOPER** | Modules | `storefuse-bridge-modules` | Renamed from Advanced; grouped cards | [modules.md](modules.md) |
| **DEVELOPER** | Extensions | `storefuse-bridge-extensions` | Existing integration hooks and companion capabilities | [extensions.md](extensions.md) |

## Legacy Slugs & Redirect Targets

| Legacy Slug | Target Slug | Handled In |
|---|---|---|
| `storefuse-bridge-general` | `storefuse-bridge-branding` | Redirect; Branding links to Store Policies |
| `storefuse-bridge-storefront` | `storefuse-bridge-setup` | Hidden menu redirect |
| `storefuse-bridge-advanced` | `storefuse-bridge-modules` | Hidden menu redirect |
| `storefuse-bridge-mobile-app` | `storefuse-bridge-app-layout` | Hidden menu redirect |
