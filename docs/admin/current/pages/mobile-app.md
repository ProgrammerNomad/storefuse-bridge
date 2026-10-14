# Mobile app (current) → App Layout (v2)

## Purpose

Flutter reference app layout via `GET /settings` → `app.ui` (schema `storefuse.app-ui.v1`). Does not change WooCommerce catalog data.

## Menu

| | |
|--|--|
| **Current slug** | `storefuse-bridge-mobile-app` |
| **View** | `admin/views/page-mobile-app.php` |
| **Hidden flag** | `_sfb_save_app_ui=1` required for checkbox saves |

## Current sidebar behavior

The current plugin displays this submenu for all `primary_client` values and does not show a client-context notice. In the v2 proposal, it is shown in the sidebar only for Flutter; authorized direct URLs remain available with an explanatory notice in other modes.

## Page layout (cards)

1. Theming & Appearance  
2. Bottom Navigation Builder  
3. Catalog (Shop, Search, Category)  
4. Search Experience  
5. Product detail  
6. Cart & Checkout Experience  
7. Home screen sections  

## Fields summary

See [fields-registry.md](../../fields-registry.md) for all `app_ui_*` keys. API shape: `StoreFuse_Bridge_App_Ui::build()`.

| Section | Keys | Flutter screens |
|---------|------|-----------------|
| Theming | `app_ui_theme_secondary_color`, `_theme_mode`, `_theme_font_family` | Global theme |
| Nav tabs | `app_ui_nav_tabs` | Bottom navigation |
| Catalog | layout, columns, shop title, search layout override, stock badge | Shop, Search, Category grids |
| Search | placeholder, trending_terms | Search |
| Product | SKU, sticky ATC, reviews, share, wishlist, related_count | PDP |
| Cart/checkout UI | coupon, free shipping bar, guest prompts, empty CTA | Cart, Checkout banners |
| Home sections | section_order, hide_sections[] | Home tab order |

### Home section IDs (checkbox values)

`announcement_bar`, `hero`, `featured_categories`, `trust_items`, `best_sellers`, `new_arrivals`, `promo_banner`.

## Not in admin UI

Product card **rating** and **sale badge** toggles are Flutter defaults only; wp-admin exposes **stock badge** via `app_ui_catalog_show_stock_badge`.

## v2 deltas

- Title: **App Layout**  
- Header note referencing `storefuse.app-ui.v1`  
- No option key changes  
