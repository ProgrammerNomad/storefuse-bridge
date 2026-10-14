# Webhooks (ISR) (v2 - planned)

## Purpose

Configure the existing outbound storefront cache-revalidation integration and inspect its delivery log. These are server-to-server revalidation requests sent by Bridge; they are not incoming payment-provider webhooks.

## Menu

| Property | Value |
|---|---|
| Slug | `storefuse-bridge-webhooks` |
| Menu title | Webhooks (ISR) |
| Logical group | Developer |
| Visibility | Next.js and Other; hidden from Flutter sidebar |
| Capability | `StoreFuse_Bridge_Admin::settings_capability()` (default `manage_woocommerce`) |

When hidden in Flutter mode, the authorized direct URL remains available and shows a brief context notice.

## Existing settings and dependencies

- `storefront_url` (configured on Setup) is the storefront host.
- `storefront_revalidate_path` (default `/api/revalidate`) is the receiving path.
- `revalidation_secret` is the shared signing secret.
- `module_webhooks_enabled` (configured on Modules) controls the outgoing revalidation module and is off by default.

Keep these existing fields and values. Do not expose the secret in public API responses or render its raw value in the delivery log.

## Planned layout

1. **Configuration form:** storefront URL summary, revalidation path, secret field, and clear explanation of the signed request.
2. **Module status:** show whether the Webhooks module is enabled, with a link to Modules if it is off.
3. **Test delivery:** use the existing `storefuse_bridge_test_webhook` admin AJAX action and show a clear success/failure result, including HTTP response status where available.
4. **Delivery log:** show useful timestamps and outcome/status details; never show the secret or signed credential. A clear-log action uses the existing `storefuse_bridge_clear_webhook_log` AJAX action.

## Form and security

The configuration form uses `settings_fields( 'storefuse_bridge_settings_group' )` and saves only `storefront_revalidate_path` and `revalidation_secret` to the existing settings option. The secret input is blank on every render: submitting it blank preserves the existing secret, entering a new value replaces it, and the explicit `_sfb_clear_revalidation_secret=1` helper clears it. The helper is not stored in the settings option. The storefront host is edited on Setup. Test and clear-log buttons are separate AJAX actions, use the existing admin nonce, and require the existing settings capability.

See [AJAX & actions](../../ajax-and-actions.md) and [the current Storefront & Clients screen](../../current/pages/storefront-clients.md) for the shipped behavior.
