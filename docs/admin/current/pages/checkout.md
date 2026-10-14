# Checkout

## Purpose

Redirect vs headless checkout mode for all headless clients. Complements WooCommerce **Payments** configuration.

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-checkout` |
| **View** | `admin/views/page-checkout.php` |

## Page layout

1. **Checkout Mode** - radio redirect / headless  
2. **Redirect Mode Settings** - `#sfb-panel-redirect`  
3. **Headless Mode Settings** - `#sfb-panel-headless` (read-only gateway list)  

## Fields

| Label | Key | ID | API |
|-------|-----|-----|-----|
| Mode | `checkout_mode` | `sfb-mode-redirect`, `sfb-mode-headless` | `GET /checkout/config`, `features.headless_checkout` |
| Checkout button label | `checkout_redirect_label` | `sfb-checkout-redirect-label` | redirect label in config |
| Checkout page URL override | `checkout_page_url` | `sfb-checkout-page-url` | `POST /checkout/redirect-url` target |

Values: `checkout_mode` ∈ `redirect` | `headless`.

## Non-settings UI

Active payment gateways list from WooCommerce (read-only).

## JavaScript

`initCheckoutPanels()` toggles redirect/headless panels on radio change.

## v2 deltas

Contextual note for Flutter: redirect + WebView typical; headless aimed at Store API web storefronts.

## Flutter

Uses `GET /checkout/config` + `app.ui.screens.checkout.show_guest_prompt`. Mode from Bridge checkout settings, not App Layout alone.
