# Checkout (v2 - planned)

## Purpose

Configure the existing StoreFuse checkout mode, button label, and optional WooCommerce checkout URL. These settings feed the existing checkout configuration and status responses; the screen does not add a payment gateway integration.

## Menu

| Property | Value |
|---|---|
| Slug | `storefuse-bridge-checkout` |
| Menu title | Checkout |
| Logical group | Commerce |
| Visibility | All client modes |
| Capability | `StoreFuse_Bridge_Admin::settings_capability()` (default `manage_woocommerce`) |

## Existing settings

- `checkout_mode`: `redirect` or `headless` (default `redirect`).
- `checkout_redirect_label`: label shown by a client for its checkout action.
- `checkout_page_url`: optional checkout URL override; blank uses the WooCommerce checkout page.

Keep the existing option keys and checkout routes. The Bridge supports `GET /checkout/config`, `POST /checkout`, `POST /checkout/redirect-url`, `GET /checkout/payment-methods`, and `GET /checkout/shipping-methods`. There is no `/checkout/process` route.

## Guidance and behavior

### Redirect mode

Explain that the client hands checkout to the WooCommerce checkout page. Flutter clients use the existing `POST /checkout/redirect-url` handoff and open the returned URL in a WebView or external browser as appropriate. The handoff restores the WooCommerce session; follow [the Flutter checkout guide](../../../clients/mobile-flutter.md) for the client flow.

Do not promise that every gateway works in every WebView. Compatibility depends on the merchant's installed gateway, redirects, and device/browser behavior. The store owner should test each configured payment method in the target app/browser. See [checkout and async payments](../../../clients/checkout-payments.md).

### Headless mode

Explain that clients submit checkout through `POST /checkout` using the documented WooCommerce session and nonce requirements. Gateway behavior depends on the gateway integration; Bridge does not ship native payment SDKs. Some gateways require a redirect or a companion integration. Link to the payment-method and pending-order guidance above.

### Related settings

- Guest prompts are configured under App Layout (`app_ui_checkout_show_guest_prompt`).
- Return policy and free-shipping copy are configured under Store Policies.
- Gateway configuration remains under WooCommerce → Settings → Payments.
- Disabling the Checkout module removes its checkout routes; cross-link to Modules and explain this impact.

## Form and fields

Use the existing settings group and nonce via `settings_fields( 'storefuse_bridge_settings_group' )`. The form saves only `checkout_mode`, `checkout_redirect_label`, and `checkout_page_url` into the existing `storefuse_bridge_settings` option. Render the standard settings save notice.
