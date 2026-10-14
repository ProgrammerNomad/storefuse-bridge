# Social & Trust

## Purpose

Social profile URLs and configurable trust badges for settings and homepage.

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-social` |
| **View** | `admin/views/page-social.php` |
| **v2** | Unchanged |

## Page layout

1. **Social media links** - six platforms  
2. **Trust badges** - repeater UI  

## Fields - Social

| Platform | Key | Input type | ID pattern |
|----------|-----|------------|------------|
| Instagram | `social_instagram` | url | `sfb_social_instagram` |
| Facebook | `social_facebook` | url | |
| Twitter / X | `social_twitter` | url | |
| YouTube | `social_youtube` | url | |
| Pinterest | `social_pinterest` | url | |
| WhatsApp | `social_whatsapp` | text (phone or URL) | |

API: `GET /settings` → `social_links`.

## Fields - Trust badges

Hidden: `trust_badges` JSON (`#sfb-trust-badges-json`). Rows in `#sfb-trust-badges`: enabled, icon, title, description. `#sfb-add-badge`, `.sfb-remove-badge`.

API: `settings.trust_badges` and `GET /homepage` → `trust_items` (enabled only).

## JavaScript

`serializeBadges()` on input change and form submit.
