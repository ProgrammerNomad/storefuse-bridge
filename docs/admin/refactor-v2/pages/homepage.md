# Homepage (v2 - planned)

## Purpose

Configures homepage merchandising content blocks served by `GET /homepage`: Hero banner, Featured Categories, Best Sellers, New Arrivals, and Promotional Banner.

## Menu (planned)

| Property | Value |
|---|---|
| **Slug** | `storefuse-bridge-homepage` |
| **Menu Title** | `Homepage` |
| **Parent Group** | `STORE CONTENT` |
| **Capability** | `StoreFuse_Bridge_Admin::settings_capability()` (`manage_woocommerce`) |

## Sidebar Visibility

All clients (`flutter`, `nextjs`, `other`).

---

## v2 Client-Aware Behavioral Adaptations

### 1. Hero Section Adaptations
- **Web Storefronts (`nextjs` / `other`)**:
  Full hero editing form is prominently displayed:
  - `hero_badge_text`, `hero_headline`, `hero_headline_highlight`, `hero_subheadline`
  - `hero_cta_primary_label`, `hero_cta_primary_href`
  - `hero_cta_secondary_label`, `hero_cta_secondary_href`
  - `hero_image_id` (WordPress Media uploader)
  - `hero_rating_text`, `hero_shipping_text`
- **Flutter Mobile App (`primary_client === 'flutter'`)**:
  - The Flutter reference app can use native promotional widgets and layout blocks configured under **App Layout** rather than web-style hero text/buttons.
  - In the Flutter admin view, the hero form is collapsed by default inside an informative expandable card. All existing hero fields remain available and saveable:
    > **Mobile App Notice:** The Flutter app uses native banner cards and the section layout from **App Layout**. Web hero settings below are only needed if you also serve a web client.
  - Allows Flutter store managers to expand the card if they also operate a web frontend.

### 2. App Preview Block
- `<details class="sfb-card sfb-phone-preview">` with `#sfb-preview-load` button.
- Shown when `primary_client === 'flutter'`.
- Hidden or omitted when `primary_client` is `nextjs` or `other`.

### 3. Cross-Link Updates
- Notice updated: Announcement bar is managed under **Branding** (`admin.php?page=storefuse-bridge-branding`) instead of the deprecated General slug.

---

## Universal Merchandising Sections (All Clients)

### 1. Featured Categories
- Interactive row builder (up to 6 categories).
- Custom title override, custom image picker, and direct link.
- Stored as JSON string in `featured_categories`.

### 2. Best Sellers Block
- `homepage_best_sellers_heading` (Default: `Best Sellers`).
- `homepage_best_sellers_source`:
  - `best-selling` (Automatic WooCommerce sales count)
  - `featured` (WooCommerce starred/featured products)
  - `manual` (Manual product ID picker via `#sfb-product-selection`)
- `homepage_best_sellers_count` (1–24, default 8).
- `homepage_best_sellers_ids` (CSV product IDs for manual source).

### 3. New Arrivals Block
- `homepage_new_arrivals_heading` (Default: `New Arrivals`).
- `homepage_new_arrivals_count` (1–24, default 8).
- `homepage_new_arrivals_category` (Optional category slug filter).

### 4. Promotional Banner Block
- `homepage_promo_banner_enabled` (Toggle checkbox).
- `homepage_promo_banner_headline`, `homepage_promo_banner_body`.
- `homepage_promo_banner_cta_label`, `homepage_promo_banner_cta_href`.
- `homepage_promo_banner_bg_color` (Color picker, default `#1e293b`).

---

## Fields Registered

All fields map to `storefuse_bridge_settings`:
`hero_badge_text`, `hero_headline`, `hero_headline_highlight`, `hero_subheadline`, `hero_cta_primary_label`, `hero_cta_primary_href`, `hero_cta_secondary_label`, `hero_cta_secondary_href`, `hero_image_id`, `hero_rating_text`, `hero_shipping_text`, `featured_categories`, `homepage_best_sellers_heading`, `homepage_best_sellers_source`, `homepage_best_sellers_count`, `homepage_best_sellers_ids`, `homepage_new_arrivals_heading`, `homepage_new_arrivals_count`, `homepage_new_arrivals_category`, `homepage_promo_banner_enabled`, `homepage_promo_banner_headline`, `homepage_promo_banner_body`, `homepage_promo_banner_cta_label`, `homepage_promo_banner_cta_href`, `homepage_promo_banner_bg_color`.
