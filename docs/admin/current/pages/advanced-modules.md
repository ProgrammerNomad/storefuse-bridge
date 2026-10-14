# Advanced (current) → Modules (v2)

## Purpose

Enable/disable REST modules (`GET /status` → `modules`).

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-advanced` |
| **v2 slug** | `storefuse-bridge-modules` |
| **View** | `admin/views/page-advanced.php` |
| **Hidden** | `_sfb_save_modules=1` |

## Page layout

Single card: **Modules (matches GET /status)** - always-on rows + toggleable checkboxes with optional route `<details>`.

## Fields

For each toggleable module ID: `module_{id}_enabled` checkbox.

Always-on (display only): `status`, `auth`.

Toggleable IDs: settings, products, categories, search, attributes, tags, cart, checkout, account, orders, addresses, wishlist, reviews, posts, utils, downloads, webhooks.

## v2 deltas

Group labels: Core, Catalog, Commerce, Content, Integrations. Page title **Modules**.
