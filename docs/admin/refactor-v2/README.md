# Admin Refactor v2 (Planned Architecture & Specs)

**Proposal only; implementation has not started.** This directory contains the target information architecture, implementation plan, acceptance checklist, and page specifications for all 13 screens.

The proposal reuses the existing `storefuse_bridge_settings` option and keys. **No database migration, backfill, or data conversion is required or in scope.** It also preserves current REST routes and response contracts.

## Documentation Index

| Document | Purpose |
|---|---|
| [IMPLEMENTATION-PLAN.md](IMPLEMENTATION-PLAN.md) | Scope, behavior rules, implementation sequence, existing contracts, and review decisions |
| [target-sidebar.md](target-sidebar.md) | Flat WordPress submenu order, five logical groups, visibility matrix, direct page access, and legacy redirects |
| [refactor-acceptance.md](refactor-acceptance.md) | Verification gates for API contracts, client-aware presentation, direct URLs, forms, security, and accessibility |
| [pages/](pages/) | Comprehensive specifications for **all 13 screens** (Dashboard, Setup, Homepage, Social & Trust, Branding, App Layout, Navigation, Checkout, Store Policies, API & CORS, Webhooks, Modules, Extensions) |

## Supporting Documentation

- **Current Architecture**: Documented under [../current/](../current/) and [../current/sidebar.md](../current/sidebar.md).
- **Settings Registry**: [../fields-registry.md](../fields-registry.md) maps every single option key to its v2 destination page and consumer endpoint.
- **Client Mapping**: [../client-mapping.md](../client-mapping.md) details how wp-admin settings correlate to Flutter reference app features and Next.js storefronts.
- **AJAX & Actions**: [../ajax-and-actions.md](../ajax-and-actions.md) documents nonce requirements and handlers.
- **UX Patterns**: [../ux-patterns.md](../ux-patterns.md) outlines admin design standards (`.sfb-card`, page chrome, save alerts).
