# Extension API v0.2 (specification only)

**Status:** Document only - no v0.2 module registration code ships in Bridge 0.1.0.

This spec defines how companion plugins will register additional Bridge modules without patching core.

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

**Semantics:**

- Runs once during `StoreFuse_Bridge::load_modules()` before routes register.
- Companion plugins append **instances** of classes extending `StoreFuse_Bridge_Module`.
- Core modules are registered first; companions load after unless priority dictates otherwise.
- Duplicate `$id` values: **companion loses** - core ID wins; companion should log an admin notice.

---

## Module contract (`StoreFuse_Bridge_Module`)

| Method / property | Requirement |
|-------------------|-------------|
| `protected string $id` | Unique snake-case id (e.g. `loyalty`) |
| `register_routes()` | Register only when `is_enabled()` is true |
| `is_enabled()` | Reads `module_{id}_enabled` from settings; default **false** for companion modules unless admin enables |
| Settings key | `storefuse_bridge_settings['module_{id}_enabled']` |

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

Do **not** implement this filter in production Bridge until:

1. [extensions.md](extensions.md) matches shipped hook signatures.
2. This document is reviewed against `class-plugin.php` module loader.
3. Migration path for sites using only filters (no modules) is documented.
