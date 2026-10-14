> Historical proposal. Bridge 1.1 applies an instance-list module filter, not the descriptor registry and admin settings UI proposed below. See [the current extension contract](release-1.1.md).

# Extension API v0.2 (proposal)

**Status:** The current plugin applies the `storefuse_bridge_modules` filter during module initialization. This document describes additional discovery and admin-registration features that are not implemented. Treat each behavior below as a proposal unless confirmed in the current source.

This proposal explores how companion plugins can integrate additional Bridge modules without patching core. It is not an installed-plugin registry or a shipped third-party settings UI.

---

## Goals

- Allow `storefuse-bridge-{slug}` plugins to add routes under `storefuse/v1` (or a documented sub-prefix).
- Preserve core module IDs and settings keys.
- Fail safely on version skew and ID collisions.

---

## `storefuse_bridge_modules` filter

```php
/**
 * @param StoreFuse_Bridge_Module[] $modules Core module instances.
 * @return StoreFuse_Bridge_Module[]
 */
$modules = apply_filters( 'storefuse_bridge_modules', $modules );
```

**Runtime behavior currently present:**

- Runs once during `StoreFuse_Bridge::load_modules()` before routes register.
- Companion plugins append **instances** of classes extending `StoreFuse_Bridge_Module`.
- The loader registers routes for each supplied instance that is a `StoreFuse_Bridge_Module` and whose `is_enabled()` method returns true.
- The base `is_enabled()` implementation returns true when the corresponding setting is absent. Do not assume a companion module is disabled by default or can be toggled in wp-admin.

The current filter does not provide plugin discovery, duplicate-ID protection, a companion-module settings registration UI, or module controls on the existing Advanced page.

---

## Module contract (`StoreFuse_Bridge_Module`)

| Method / property | Requirement |
|-------------------|-------------|
| `protected string $id` | Unique snake-case id (e.g. `loyalty`) |
| `register_routes()` | Register only when `is_enabled()` is true |
| `is_enabled()` | The base implementation checks `module_{id}_enabled` and currently defaults to **true** when no value exists. A future proposal may introduce explicit opt-in for companion modules. |
| Settings key | The base method reads `storefuse_bridge_settings['module_{id}_enabled']`; the current admin sanitizer/UI only recognizes the core toggleable module IDs. |

Companion modules **must not** replace core `$id` values: `products`, `cart`, `checkout`, `auth`, etc.

---

## Dependencies and compatibility

- Companion plugin header: `Requires Plugins: woocommerce, storefuse-bridge`
- Runtime check: `version_compare( STOREFUSE_BRIDGE_VERSION, '0.2.0', '>=' )` before registering modules.
- Optional admin **Extensions** screen (future): list companions, version, declared Bridge minimum.

---

## Naming and packaging

- Plugin slug: `storefuse-bridge-{feature}` (e.g. `storefuse-bridge-loyalty`).
- Text domain: match plugin slug.
- REST routes: prefer `storefuse/v1/{resource}`; store-specific experimental APIs may use `storefuse/v1/ext/{slug}/...` if documented.

---

## Route policy

| Type | Prefix | Auth |
|------|--------|------|
| Store-wide commerce | `storefuse/v1/*` | Same tiers as core ([verified-routes.md](verified-routes.md)) |
| Store-specific extension | `storefuse/v1/ext/{slug}/*` | Document per route; default authenticated |

New core commerce routes remain in core modules until promoted from companion experiments.

---

## Settings and discovery (future)

- Companion registers settings fields via `storefuse_bridge_admin_settings_sections` (proposed v0.2 - not implemented).
- Module toggle appears on **Advanced** when companion calls registration API.

---

## Implementation gate

Before describing future discovery or settings UI as shipped:

1. [extensions.md](extensions.md) matches shipped hook signatures.
2. This document is reviewed against `class-plugin.php` module loader.
3. The proposed behavior is checked against the current module loader, sanitization, and admin capabilities.
