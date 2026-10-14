# Modules (v2 - planned)

## Purpose

Enables or disables individual REST API modules. Disabled modules register no REST routes, reducing endpoint exposure and overhead. Matches `GET /status` API output.

Renamed from **Advanced** to **Modules** to align with its true purpose.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-modules` |
| **Menu Title** | `Modules` |
| **Parent Group** | `DEVELOPER` |
| **Legacy Redirect** | `storefuse-bridge-advanced` → `storefuse-bridge-modules` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

All clients (`flutter`, `nextjs`, `other`).

---

## Planned Sections & Layout

### 1. Form Header & Critical Flag
- Standard form wrapping `<form method="post" action="options.php">`.
- `settings_fields( 'storefuse_bridge_settings_group' );`
- **MANDATORY HIDDEN FIELD**:
  ```html
  <input type="hidden" name="storefuse_bridge_settings[_sfb_save_modules]" value="1" />
  ```
  *Required so that unchecking module checkboxes persists the disabled state during `StoreFuse_Bridge_Admin::sanitize_settings()`.*

### 2. Grouped Modules Architecture
Instead of an unorganized 19-row flat table, organize modules into 5 clear intent-driven cards:

#### Group A: Core & System
- `status` - **Always on** (Non-toggleable badge)
- `auth` - **Always on** (Non-toggleable badge)
- `settings` - Store config & client settings (`/storefuse/v1/settings`)
- `utils` - Utility helpers and endpoints

#### Group B: Catalog & Discovery
- `products` - Product listing and single product data (`/products`, `/products/<id>`)
- `categories` - Product categories taxonomy (`/categories`)
- `search` - Product search endpoint (`/search`)
- `attributes` - Product variations & attribute terms (`/attributes`)
- `tags` - Product tags taxonomy (`/tags`)

#### Group C: Commerce & Checkout
- `cart` - Session/tokenized cart operations (`/cart`) *(Warning: Disabling breaks shopping cart)*
- `checkout` - Checkout configuration and processing (`/checkout`) *(Warning: Disabling breaks checkout)*
- `orders` - Customer order history and detail (`/orders`) *(Warning: Disabling breaks order lookup)*
- `addresses` - Customer billing/shipping address management (`/addresses`)

#### Group D: Customer & Social
- `account` - Customer profile and password reset (`/account`)
- `wishlist` - Product wishlist management (`/wishlist`)
- `reviews` - Product ratings and customer reviews (`/reviews`)

#### Group E: Content & Integrations
- `posts` - WordPress blog posts (`/posts`)
- `downloads` - Customer digital downloads (`/downloads`)
- `webhooks` - Outgoing ISR cache revalidation triggers (`/webhooks`)

### 3. Contextual Warnings & Route Map
- **Disabling Impact Warning**: Shown on modules whose disablement commonly breaks or removes a core client flow (`settings`, `cart`, `checkout`, `orders`, `account`). Use a specific explanation for the affected routes instead of claiming every disabled module breaks all commerce flows.
- **Collapsible Affected Routes**: Each toggleable module includes an expandable `<details>` summary listing its registered REST endpoints (relative to `/wp-json/storefuse/v1`, from `StoreFuse_Bridge_Admin::module_route_map()`).
- **Webhooks Module Notice**:
  When `webhooks` is disabled, display a tip linking to Webhooks:
  *"ISR webhooks will not fire while this module is disabled. See [Webhooks (ISR)](webhooks-isr.md).* The admin destination is `admin.php?page=storefuse-bridge-webhooks`.

The `webhooks` toggle is off by default. The other toggleable modules default on; `status` and `auth` are always on and have no checkbox.

---

## Fields Registered

- Hidden batch trigger: `_sfb_save_modules`
- Checkboxes: `module_{id}_enabled` for each toggleable module:
  `settings`, `products`, `categories`, `search`, `attributes`, `tags`, `cart`, `checkout`, `account`, `orders`, `addresses`, `wishlist`, `reviews`, `posts`, `utils`, `downloads`, `webhooks`.
