# Social & Trust (v2 - planned)

## Purpose

Configures social media profile links and custom Trust Badges served by `GET /settings` under `social_links` and `trust_badges`, and consumed on the mobile app home screen and web footers.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-social` |
| **Menu Title** | `Social & Trust` |
| **Parent Group** | `STORE CONTENT` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

All clients (`flutter`, `nextjs`, `other`).

---

## Planned Sections & Layout

### 1. Social Media Links
Profile URLs consumed across clients:
- `social_instagram`: Instagram URL
- `social_facebook`: Facebook URL
- `social_twitter`: X / Twitter URL
- `social_youtube`: YouTube Channel URL
- `social_pinterest`: Pinterest URL
- `social_whatsapp`: WhatsApp phone number or link (API normalizes numbers to `https://wa.me/{number}`)

### 2. Trust Badges Builder
Interactive JSON builder for store trust guarantees:
- Visual list with `Add Badge` button and row deletion.
- Badge properties:
  - `icon`: short text/symbol value displayed by the clients (the current builder uses text or symbols, including emoji; do not require Dashicons or SVG markup).
  - `title`: Primary headline (e.g., "Free Shipping", "Secure Payment", "24/7 Support").
  - `description`: Supporting detail text (e.g., "On all orders over $50").
  - `enabled`: whether the badge is active.
- Stored as JSON string in `trust_badges`.
- The current form displays four starter rows when no badge data exists; those rows become saved data only after the merchant saves the form. The settings default remains an empty list.
- The builder supports up to 12 badges, matching the server-side sanitizer.
- Exposed via:
  - `GET /settings` → `trust_badges`
  - `GET /homepage` → `trust_items` (when section included in layout)

---

## Fields Registered

All fields map to `storefuse_bridge_settings`:
`social_instagram`, `social_facebook`, `social_twitter`, `social_youtube`, `social_pinterest`, `social_whatsapp`, `trust_badges`.
