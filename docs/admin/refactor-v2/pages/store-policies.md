# Store Policies (v2 - planned)

## Purpose

Edit existing store policy and shipping-threshold settings exposed by the Store API and used by client UI. This page separates these fields from Branding; it does not add a policy engine or new data storage.

## Menu

| Property | Value |
|---|---|
| Slug | `storefuse-bridge-policies` |
| Menu title | Store Policies |
| Logical group | Commerce |
| Visibility | All client modes |
| Capability | `StoreFuse_Bridge_Admin::settings_capability()` (default `manage_woocommerce`) |

## Fields and behavior

- `return_policy_days`: non-negative number of days used in the store information response.
- `free_shipping_threshold_label`: merchant-authored label shown with the threshold.
- `free_shipping_threshold_amount`: non-negative amount in the store currency. Link to WooCommerce currency settings rather than introducing another currency field.

These values are returned through `GET /settings` under `store`. The free-shipping threshold is also used by the Flutter cart progress UI when enabled in App Layout.

## Form and security

Use the existing `storefuse_bridge_settings_group` settings form and nonce. Save only the three listed keys into `storefuse_bridge_settings`, and show the standard settings confirmation. Preserve values when another page is saved.

Source screen: [General](../../current/pages/general.md).
