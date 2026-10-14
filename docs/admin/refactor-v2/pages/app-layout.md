# App Layout (v2 - planned)

## Purpose

Controls the layout, appearance, navigation tabs, screen behaviors, and product card settings for the StoreFuse Flutter mobile application. Exposed via REST API at `GET /settings` under `app.ui` (`storefuse.app-ui.v1`).

Renamed from **Mobile app** to **App Layout** to represent its role in the Appearance section.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-app-layout` |
| **Menu Title** | `App Layout` |
| **Parent Group** | `APPEARANCE` |
| **Legacy Redirect** | `storefuse-bridge-mobile-app` → `storefuse-bridge-app-layout` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

- **Visible in sidebar**: `primary_client === 'flutter'`
- **Hidden from sidebar**: `primary_client === 'nextjs'` or `'other'`
- **Direct URL Access**: Fully accessible by direct URL (`admin.php?page=storefuse-bridge-app-layout`) for all clients.
  - When accessed directly while `primary_client !== 'flutter'`, displays a contextual info bar at the top:
    > **Note:** This screen configures the Flutter mobile app layout. It is hidden from your sidebar because your primary client is set to **[Client Name]**. Changes saved here will still be served to mobile app clients.

---

## Planned Sections & Layout

### 1. Form Header & Critical Flag
- Standard form wrapping `<form method="post" action="options.php">`.
- `settings_fields( 'storefuse_bridge_settings_group' );`
- **MANDATORY HIDDEN FIELD**:
  ```html
  <input type="hidden" name="storefuse_bridge_settings[_sfb_save_app_ui]" value="1" />
  ```
  *Required so that unchecked checkboxes (which are omitted from PHP `$_POST`) are correctly unset during `StoreFuse_Bridge_App_Ui::sanitize_settings_patch()`.*

### 2. Theming & Appearance
Controls mobile visual accents:
- `app_ui_theme_secondary_color` (Hex color picker, default `#0F172A`)
- `app_ui_theme_mode` (`system`, `light`, `dark`)
- `app_ui_theme_font_family` (Text input, e.g., `Default`, `Inter`, `Roboto`)

*(Note: Primary color `app_primary_color` is configured on the **Branding** page and acts as the shared color seed).*

### 3. Bottom Navigation Builder
Configures active tabs in the Flutter bottom navigation bar:
- `app_ui_nav_tabs`: Comma-separated string of tab identifiers.
  - Allowed tabs: `home`, `shop`, `search`, `cart`, `account`, `wishlist`.
  - Default: `home,shop,search,cart,account`.
  - Explanatory note: Tab display also respects module activation (e.g., `wishlist` tab requires the Wishlist module to be active).

### 4. Catalog (Shop, Search, Category Screens)
- `app_ui_catalog_layout`: `grid` | `list`.
- `app_ui_catalog_columns`: `2` | `3`.
- `app_ui_shop_title`: Screen title text (default: `Products`).
- `app_ui_search_layout`: Inherit catalog default, or force `grid` | `list`.
- `app_ui_catalog_show_stock_badge`: Checkbox toggle for "In Stock / Out of Stock" badge on product cards.

### 5. Product Screen (PDP)
Toggles for individual components on the Flutter product detail page:
- `app_ui_product_show_sku`: Show SKU code.
- `app_ui_product_sticky_add_to_cart`: Sticky floating Add to Cart button at bottom.
- `app_ui_product_show_reviews`: Customer reviews tab/section.
- `app_ui_product_show_share`: Social share button on product header.
- `app_ui_product_show_wishlist`: Add to wishlist heart button (requires Wishlist module).
- `app_ui_product_related_count`: Number of related product recommendations (0–12, default `4`).

### 6. Cart & Checkout UX
- `app_ui_cart_show_coupon`: Coupon input field in cart.
- `app_ui_cart_show_free_shipping_bar`: Free shipping progress bar (threshold configured in **Store Policies**).
- `app_ui_cart_show_guest_prompt`: Banner prompting guest users to log in for saved addresses and faster checkout.
- `app_ui_checkout_show_guest_prompt`: Banner prompting guest users to log in on checkout.
- `app_ui_cart_empty_cta_href`: Target path for empty cart button (default: `/shop`).

### 7. Home Screen Section Order & Visibility
Controls the ordering and visibility of sections on the Flutter app home screen:
- `app_ui_home_section_order`: Comma-separated section IDs in the form, normalized to an ordered list (`HOME_SECTION_IDS`: `announcement_bar`, `hero`, `featured_categories`, `trust_items`, `best_sellers`, `new_arrivals`, `promo_banner`).
- `app_ui_home_hide_sections[]`: Array of section IDs to hide from the mobile home view.
- Cross-link note: *"Section content and product feeds (Best Sellers, Categories, Promo) are customized on the **Homepage** page."*

---

## Fields Registered

All fields map to `storefuse_bridge_settings`:
`app_ui_theme_secondary_color`, `app_ui_theme_mode`, `app_ui_theme_font_family`, `app_ui_nav_tabs`, `app_ui_catalog_layout`, `app_ui_catalog_columns`, `app_ui_shop_title`, `app_ui_search_layout`, `app_ui_catalog_show_stock_badge`, `app_ui_product_show_sku`, `app_ui_product_sticky_add_to_cart`, `app_ui_product_show_reviews`, `app_ui_product_show_share`, `app_ui_product_show_wishlist`, `app_ui_product_related_count`, `app_ui_cart_show_coupon`, `app_ui_cart_show_free_shipping_bar`, `app_ui_cart_show_guest_prompt`, `app_ui_checkout_show_guest_prompt`, `app_ui_cart_empty_cta_href`, `app_ui_home_section_order`, `app_ui_home_hide_sections`.
