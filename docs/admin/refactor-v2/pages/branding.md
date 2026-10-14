# Branding (v2 - planned)

## Purpose

Group the existing StoreFuse app color, support URL, and announcement-bar settings under one screen. WordPress site identity remains managed by the Customizer.

## Menu

| Property | Value |
|---|---|
| Slug | `storefuse-bridge-branding` |
| Menu title | Branding |
| Logical group | Appearance |
| Visibility | All client modes |
| Legacy slug | `storefuse-bridge-general` redirects here |
| Capability | `StoreFuse_Bridge_Admin::settings_capability()` (default `manage_woocommerce`) |

## Planned sections

1. **App color and support:** `app_primary_color`, `app_support_url`.
2. **Announcement bar:** the `announcement_bar_*` settings, with a preview and a link to Homepage for the other home content blocks.
3. **WordPress site identity:** read-only values or concise links to the Customizer for site name, tagline, logo, and favicon. Do not copy those WordPress settings into Bridge.
4. **Moved policy settings:** show a clear link to Store Policies. The old General slug held both branding and policy settings; redirecting to Branding must not make the policy controls hard to find.

Values exposed through `GET /settings` and `GET /homepage` keep their current shape. See the [field registry](../../fields-registry.md) and [current General screen](../../current/pages/general.md).

## Form and security

Use `settings_fields( 'storefuse_bridge_settings_group' )`, save only the existing branding and announcement keys, and render the standard settings confirmation. The Customizer section is informational and is not part of the Bridge settings form.
