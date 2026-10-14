# Settings field registry

Option: `storefuse_bridge_settings`. Sanitizer: `StoreFuse_Bridge_Admin::sanitize_settings()` + `StoreFuse_Bridge_App_Ui::sanitize_settings_patch()`.

Legend - **Target page (v2):** Dashboard | Setup | Homepage | Social & Trust | Branding | App Layout | Navigation | Checkout | Store Policies | API & CORS | Webhooks (ISR) | Modules

| Key | Type | Default (typical) | Current admin page | Target page (v2) | API / consumer | Client notes |
|-----|------|-------------------|--------------------|------------------|----------------|--------------|
| `app_primary_color` | hex | `#E85D04` | General | Branding | `GET /settings` → `app.primary_color` | Flutter theme seed |
| `app_support_url` | URL | empty | General | Branding | `GET /settings` → `app.support_url` | Flutter support link |
| `announcement_bar_enabled` | bool | true | General | Branding | `GET /settings` → `header`; `GET /homepage` → `announcement_bar` | All clients |
| `announcement_bar_text` | string | empty | General | Branding | same | |
| `announcement_bar_bg_color` | hex | `#E85D04` | General | Branding | same | |
| `announcement_bar_link` | URL | empty | General | Branding | same | |
| `return_policy_days` | int ≥0 | 7 | General | Store Policies | `GET /settings` → `store.return_policy_days` | Trust copy |
| `free_shipping_threshold_label` | string | empty | General | Store Policies | `GET /settings` → `store.free_shipping_threshold.label` | Cart bar label |
| `free_shipping_threshold_amount` | float ≥0 | 0 | General | Store Policies | `GET /settings` → `store.free_shipping_threshold.amount` | Flutter cart bar |
| `hero_badge_text` | string | empty | Homepage | Homepage | `GET /homepage` → `hero.badge_text` | Web hero; editor collapsed by default for Flutter (v2), value retained |
| `hero_headline` | string | empty | Homepage | Homepage | `hero.headline` | |
| `hero_headline_highlight` | string | empty | Homepage | Homepage | `hero.headline_highlight` | |
| `hero_subheadline` | textarea | empty | Homepage | Homepage | `hero.subheadline` | |
| `hero_cta_primary_label` | string | Shop Now | Homepage | Homepage | `hero.cta_primary_label` | |
| `hero_cta_primary_href` | URL/path | `/shop` | Homepage | Homepage | `hero.cta_primary_href` | |
| `hero_cta_secondary_label` | string | empty | Homepage | Homepage | `hero.cta_secondary_*` | |
| `hero_cta_secondary_href` | URL | empty | Homepage | Homepage | | |
| `hero_image_id` | attachment ID | 0 | Homepage | Homepage | `hero.image` (resolved URL) | Media picker |
| `hero_rating_text` | string | empty | Homepage | Homepage | `hero.rating_text` | |
| `hero_shipping_text` | string | empty | Homepage | Homepage | `hero.shipping_text` | |
| `featured_categories` | JSON array | `[]` | Homepage | Homepage | `GET /homepage` → `featured_categories` | Max 6 rows in UI |
| `homepage_best_sellers_heading` | string | Best Sellers | Homepage | Homepage | `best_sellers.heading` | |
| `homepage_best_sellers_source` | enum | `best-selling` | Homepage | Homepage | `best_sellers.source` | `best-selling`, `featured`, `manual` |
| `homepage_best_sellers_count` | int 1–24 | 8 | Homepage | Homepage | `best_sellers` product list | |
| `homepage_best_sellers_ids` | CSV IDs | empty | Homepage | Homepage | when source=manual | `#sfb-product-selection` |
| `homepage_new_arrivals_heading` | string | New Arrivals | Homepage | Homepage | `new_arrivals.heading` | |
| `homepage_new_arrivals_count` | int 1–24 | 8 | Homepage | Homepage | | |
| `homepage_new_arrivals_category` | slug | empty | Homepage | Homepage | optional category filter | |
| `homepage_promo_banner_enabled` | bool | false | Homepage | Homepage | `promo_banner.enabled` | |
| `homepage_promo_banner_headline` | string | empty | Homepage | Homepage | | |
| `homepage_promo_banner_body` | textarea | empty | Homepage | Homepage | | |
| `homepage_promo_banner_cta_label` | string | empty | Homepage | Homepage | | |
| `homepage_promo_banner_cta_href` | URL | empty | Homepage | Homepage | | |
| `homepage_promo_banner_bg_color` | hex | `#1e293b` | Homepage | Homepage | | |
| `app_ui_theme_secondary_color` | hex | `#0F172A` | Mobile app | App Layout | `GET /settings` → `app.ui.theme.secondary_color` | Flutter only |
| `app_ui_theme_mode` | enum | `system` | Mobile app | App Layout | `app.ui.theme.mode` | `system`, `light`, `dark` |
| `app_ui_theme_font_family` | string | Default | Mobile app | App Layout | `app.ui.theme.font_family` | |
| `app_ui_nav_tabs` | CSV | `home,shop,search,cart,account` | Mobile app | App Layout | `app.ui.navigation.tabs` | Allowed: home, shop, search, cart, account, wishlist |
| `app_ui_catalog_layout` | enum | `grid` | Mobile app | App Layout | `app.ui.catalog.layout` | |
| `app_ui_catalog_columns` | 2 or 3 | 2 | Mobile app | App Layout | `app.ui.catalog.columns` | |
| `app_ui_catalog_show_stock_badge` | bool | true | Mobile app | App Layout | `app.ui.catalog.show_stock_badge` | No admin toggles for rating/sale on cards |
| `app_ui_shop_title` | string | Products | Mobile app | App Layout | `app.ui.screens.shop.title` | |
| `app_ui_search_layout` | enum or empty | empty | Mobile app | App Layout | `app.ui.screens.search.layout` | Empty = inherit catalog |
| `app_ui_search_placeholder` | string | Search products... | Mobile app | App Layout | `app.ui.screens.search.placeholder` | |
| `app_ui_search_trending_terms` | CSV | empty | Mobile app | App Layout | `app.ui.screens.search.trending_terms` | |
| `app_ui_product_show_sku` | bool | true | Mobile app | App Layout | `app.ui.screens.product.show_sku` | |
| `app_ui_product_sticky_add_to_cart` | bool | true | Mobile app | App Layout | `app.ui.screens.product.sticky_add_to_cart` | |
| `app_ui_product_show_reviews` | bool | true | Mobile app | App Layout | `app.ui.screens.product.show_reviews` | |
| `app_ui_product_show_share` | bool | true | Mobile app | App Layout | `app.ui.screens.product.show_share` | |
| `app_ui_product_show_wishlist` | bool | true | Mobile app | App Layout | `app.ui.screens.product.show_wishlist` | Requires wishlist module |
| `app_ui_product_related_count` | int 0–12 | 4 | Mobile app | App Layout | `app.ui.screens.product.related_count` | |
| `app_ui_cart_show_coupon` | bool | true | Mobile app | App Layout | `app.ui.screens.cart.show_coupon` | |
| `app_ui_cart_show_free_shipping_bar` | bool | true | Mobile app | App Layout | `app.ui.screens.cart.show_free_shipping_bar` | Uses store threshold |
| `app_ui_cart_show_guest_prompt` | bool | true | Mobile app | App Layout | `app.ui.screens.cart.show_guest_prompt` | |
| `app_ui_checkout_show_guest_prompt` | bool | true | Mobile app | App Layout | `app.ui.screens.checkout.show_guest_prompt` | |
| `app_ui_cart_empty_cta_href` | path | `/shop` | Mobile app | App Layout | `app.ui.screens.cart.empty_cta_href` | |
| `app_ui_home_section_order` | Ordered section IDs (CSV form input; normalized list) | see `HOME_SECTION_IDS` | Mobile app | App Layout | `app.ui.screens.home.section_order`; also `GET /homepage` → `layout` | Flutter home order |
| `app_ui_home_hide_sections` | string[] (submitted as `app_ui_home_hide_sections[]`) | `[]` | Mobile app | App Layout | `app.ui.screens.home.hide_sections` | Checkbox per section ID |
| `social_instagram` | URL | empty | Social & Trust | Social & Trust | `GET /settings` → `social_links.instagram` | |
| `social_facebook` | URL | empty | Social & Trust | Social & Trust | | |
| `social_twitter` | URL | empty | Social & Trust | Social & Trust | | |
| `social_youtube` | URL | empty | Social & Trust | Social & Trust | | |
| `social_pinterest` | URL | empty | Social & Trust | Social & Trust | | |
| `social_whatsapp` | string | empty | Social & Trust | Social & Trust | Normalized to wa.me in API | |
| `trust_badges` | JSON array | `[]` | Social & Trust | Social & Trust | `GET /settings` → `trust_badges`; homepage `trust_items` | JS builder |
| `checkout_mode` | enum | `redirect` | Checkout | Checkout | `GET /checkout/config`; `GET /status` → `features.headless_checkout` | |
| `checkout_redirect_label` | string | Proceed to Checkout | Checkout | Checkout | `checkout/config.checkout_redirect_label` | |
| `checkout_page_url` | URL | empty | Checkout | Checkout | Redirect URL override | |
| `storefront_url` | URL | empty | Storefront & Clients | Setup | Outgoing webhook destination host (not in public settings JSON) | Used by the Webhooks module; not used by built-in password-reset email generation |
| `storefront_reset_path` | path | `/reset-password` | Storefront & Clients | Setup | No current in-plugin consumer; retained setting | Built-in reset email uses WordPress's reset URL; customization uses `storefuse_bridge_password_reset_url` |
| `primary_client` | enum | `other` | Storefront & Clients | Setup | **Admin only** (not in API) | v2 controls sidebar visibility and contextual admin guidance; it does not gate routes or settings |
| `storefront_revalidate_path` | path | `/api/revalidate` | Storefront & Clients | Webhooks (ISR) | Outgoing webhook URL path | Next.js / other |
| `revalidation_secret` | string | empty | Storefront & Clients | Webhooks (ISR) | HMAC secret | |
| `cors_enabled` | bool | false | API & Tools | API & CORS | HTTP CORS headers on `/storefuse/v1/*` | Web clients |
| `cors_allowed_origins` | textarea | empty | API & Tools | API & CORS | One origin per line | |
| `module_{id}_enabled` | bool | true except `webhooks` (false) | Advanced | Modules | `GET /status` → `modules.{id}` | See toggleable IDs below |

### Toggleable module keys (`module_{id}_enabled`)

IDs: `settings`, `products`, `categories`, `search`, `attributes`, `tags`, `cart`, `checkout`, `account`, `orders`, `addresses`, `wishlist`, `reviews`, `posts`, `utils`, `downloads`, `webhooks`.

Always-on (no checkbox): `status`, `auth`. The `webhooks` module is off by default; other toggleable modules default on.

Module save requires hidden field `_sfb_save_modules=1` on POST or unchecked modules are not updated from that form.

### Save helper flags (not stored in settings array)

| POST field | Purpose |
|------------|---------|
| `_sfb_save_app_ui` | Ensures app UI checkboxes unset when unchecked are persisted via `App_Ui::sanitize_settings_patch` |
| `_sfb_save_modules` | Gates module checkbox batch update |
| `_sfb_clear_revalidation_secret` | Explicitly clears the saved ISR signing secret; a blank `revalidation_secret` field retains the existing secret |

### Legacy compatibility aliases (not v2 fields)

The plugin retains limited input/read compatibility for older names. The v2 screens must submit the canonical keys above and must not add the aliases as new fields or migrate stored data.

| Legacy name | Canonical setting | Source behavior |
|-------------|-------------------|-----------------|
| `module_content_enabled` | `module_posts_enabled`, `module_reviews_enabled` | `StoreFuse_Bridge_Settings::all()` supplies the legacy value for either canonical module only when that canonical key is absent from the stored option. |
| `password_reset_path` | `storefront_reset_path` | `StoreFuse_Bridge_Settings::all()` uses this stored legacy value only when `storefront_reset_path` is absent. |
| `ui_catalog_layout`, `ui_catalog_columns`, `ui_show_stock_badge`, `ui_shop_title`, `ui_search_layout`, `ui_pdp_show_sku`, `ui_pdp_sticky_add_to_cart`, `ui_pdp_related_count`, `ui_cart_show_coupon`, `ui_cart_show_free_shipping_bar`, `ui_cart_empty_cta_href`, `homepage_section_order`, `homepage_section_hidden` | Corresponding `app_ui_*` settings above | `StoreFuse_Bridge_App_Ui` normalizes these legacy input names to canonical names before patch sanitization and can read them from a settings array when the canonical key is absent. They are not current form names. |

Boolean controls without a batch helper must submit an explicit false value when unchecked. In v2 this applies to `announcement_bar_enabled`, `homepage_promo_banner_enabled`, and `cors_enabled`; test both checked and unchecked saves. App Layout and Modules continue using their existing batch flags.

### Not in Bridge settings (documented on General)

| Source | API |
|--------|-----|
| WP Customizer: site title, tagline, logo, favicon | `GET /settings` → `site` |
| Nav menu locations `storefuse-header`, `storefuse-footer` | `GET /navigation`, `settings.navigation` |
| WooCommerce payment gateways | Checkout behaviour, headless gateway list |

### API-only / no admin field today

| Item | Notes |
|------|-------|
| `app.ui.product_card.show_rating`, `show_sale_badge` | Flutter defaults; only stock badge is in wp-admin |
| `product_card` block in some client docs | Align with catalog stock badge only unless admin UI added later |
