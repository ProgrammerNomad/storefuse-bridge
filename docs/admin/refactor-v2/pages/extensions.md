# Extensions (v2 - planned)

## Purpose

Help administrators understand the extension hooks and optional companion-plugin capabilities that StoreFuse Bridge already exposes. This is a read-only reference and diagnostic screen; it is not an installed-plugin scanner or extension registry.

## Menu

| Property | Value |
|---|---|
| Slug | `storefuse-bridge-extensions` |
| Menu title | Extensions |
| Logical group | Developer |
| Visibility | All client modes |
| Capability | `StoreFuse_Bridge_Admin::settings_capability()` (default `manage_woocommerce`) |

## Planned content

Keep the useful information from the current Extensions page and present it in clear, scannable sections:

1. **Companion plugins and hooks:** explain that integrations use documented WordPress actions and filters.
2. **Optional capabilities:** show the existing filters for stock alerts and push-token support and describe what enabling each capability means.
3. **Common response hooks:** list the existing settings, homepage, status, password-reset URL, and settings-updated hooks.
4. **Companion modules:** explain that Bridge applies the existing `storefuse_bridge_modules` instance filter. Link to the current Bridge 1.1 module contract; do not imply the admin page controls companion-module settings.
5. **Documentation link:** use the existing configurable documentation URL.

## Out of scope

- Do not claim that Bridge discovers installed companion plugins or displays their versions/status; the current plugin has no `StoreFuse_Bridge_Extension_Registry` or `storefuse_bridge_register_extension` registry action.
- The `storefuse_bridge_modules` instance filter exists at runtime. The separate v0.2 proposal for discovery and admin settings is not a shipped feature.
- Do not add settings fields, database storage, or a form to this screen.

## Page behavior

Read-only. No `options.php` form or submit button. External documentation links open safely in a new tab and include `rel="noopener"`.
