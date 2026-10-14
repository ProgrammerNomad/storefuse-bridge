# General (current)

## Purpose

Branding, announcement bar, store policies, and read-only site identity. **v2 splits** into [Branding](../../refactor-v2/pages/branding.md) + [Store Policies](../../refactor-v2/pages/store-policies.md).

## Menu

| | |
|--|--|
| **Current slug** | `storefuse-bridge-general` |
| **View** | `admin/views/page-general.php` |
| **v2** | Redirect → `storefuse-bridge-branding` + `storefuse-bridge-policies` |
| **Form** | `POST options.php`, `settings_fields('storefuse_bridge_settings_group')` |

## Page layout

1. **App branding** - primary colour, support URL  
2. **Announcement bar** - enable, text, colour, link  
3. **Store policies** - return days, free shipping label/amount  
4. **Site identity** - read-only + Customizer links  

## Fields

| Label | Control | Key | ID | Type | Default | API |
|-------|---------|-----|-----|------|---------|-----|
| App primary colour | color picker | `app_primary_color` | `sfb-app-colour` | hex | `#E85D04` | `settings.app.primary_color` |
| Support URL | url | `app_support_url` | `sfb-support-url` | URL | empty | `settings.app.support_url` |
| Enable announcement bar | checkbox | `announcement_bar_enabled` | - | bool | off | `settings.header`, homepage `announcement_bar` |
| Announcement text | text | `announcement_bar_text` | `sfb_bar_text` | string | | |
| Background colour | color | `announcement_bar_bg_color` | `sfb_bar_color` | hex | | |
| Link URL | url | `announcement_bar_link` | `sfb_bar_link` | URL | | |
| Return policy days | number min 0 | `return_policy_days` | `sfb_return_days` | int | 7 | `settings.store.return_policy_days` |
| Free shipping label | text | `free_shipping_threshold_label` | `sfb_shipping_label` | string | | `settings.store.free_shipping_threshold` |
| Free shipping amount | number step 0.01 | `free_shipping_threshold_amount` | `sfb_shipping_amount` | float | 0 | cart progress bar |

## Non-settings UI

Site name, tagline, logo URL, favicon URL (read-only). Buttons: Customizer title/tagline, logo, favicon.

## JavaScript

`.sfb-color-picker` → WP Color Picker. Unsaved-changes `beforeunload` on all `.sfb-admin form`.

## Cross-links

Homepage notes announcement bar is configured **here only**.

## v2 deltas

Policies card → [store-policies.md](../../refactor-v2/pages/store-policies.md). Branding + announcement + site identity → [branding.md](../../refactor-v2/pages/branding.md).
