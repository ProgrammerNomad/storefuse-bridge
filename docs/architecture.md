# Architecture - StoreFuse Bridge

## What This Plugin Is

StoreFuse Bridge is the **headless application layer WooCommerce never had officially**. It sits between WordPress/WooCommerce and any headless storefront (StoreFuse + Next.js) and provides a single, clean, versioned REST API namespace.

**StoreFuse Bridge is the exclusive API for the StoreFuse storefront.** The frontend never calls `wc/v3`, `wc/store/v1`, or WPGraphQL. It calls `/storefuse/v1/*` only. This is the same pattern Shopify Hydrogen uses with Storefront API - the frontend is insulated from all internal commerce system changes.

The plugin is:

- **Storefront-optimised** - returns pre-aggregated data shaped for rendering pages, not managing an admin
- **Cached** - product/category data served from transients, not live DB queries on every request
- **Extensible** - every response passes through WordPress filters so other plugins can add data
- **WooCommerce-version-resilient** - uses WC functions/hooks, not direct DB queries
- **Normalising** - WooCommerce internal concepts (post meta, taxonomy internals, field names) never reach the frontend

The plugin has **no frontend output**. It adds nothing to the WordPress theme. The only UI it adds is the admin settings page under WooCommerce → StoreFuse Bridge.

---

## Folder Structure

```
storefuse-bridge/
│
├── storefuse-bridge.php              ← Entry point, plugin header, bootstrap
│
├── includes/
│   ├── class-plugin.php              ← Main class, wires all modules together
│   ├── class-module.php              ← Abstract base class all modules extend
│   ├── class-settings.php            ← Read/write plugin options (wp_options)
│   ├── class-cache.php               ← Transient cache helpers + invalidation hooks
│   ├── class-admin.php               ← Admin menu + settings page
│   ├── class-response.php            ← Shared response builder helpers
│   │
│   └── modules/
│       ├── class-module-status.php       ← GET /status
│       ├── class-module-settings.php     ← GET /settings, GET /navigation, GET /homepage
│       ├── class-module-products.php     ← GET /products, GET /products/{slug}
│       ├── class-module-categories.php   ← GET /categories, GET /categories/{slug}
│       ├── class-module-search.php       ← GET /search
│       ├── class-module-cart.php         ← GET/POST/PUT/DELETE /cart/*
│       ├── class-module-checkout.php     ← GET/POST /checkout, GET /orders/{key}
│       ├── class-module-content.php      ← GET/POST /reviews, GET /posts
│       └── class-module-webhooks.php     ← Outgoing ISR revalidation webhooks
│
├── admin/
│   └── views/
│       └── settings-page.php         ← Admin UI HTML template
│
├── assets/
│   ├── admin.css
│   └── admin.js                      ← Tab switching, colour picker (WP core)
│
├── languages/
│   └── storefuse-bridge.pot
│
└── docs/
    ├── api-reference.md
    └── architecture.md
```

---

## Data Normalisation Rule

This is the most important architectural constraint: **no WordPress or WooCommerce internal concepts leak through the API to the frontend**.

The frontend does not know about:
- `post_meta` keys (no `_rank_math_title`, no `_yoast_wpseo_metadesc`, no `_price`)
- WP taxonomy internals (no `term_id`, no `term_taxonomy_id`)
- `wp_options` key names
- ACF field names or group IDs
- WooCommerce `_sku`, `_stock`, `_sale_price` raw meta keys

The plugin normalises everything into clean, semantically named fields:

```
WooCommerce Internal         StoreFuse API Output
---------------------------------------
_price (meta)                product.price_raw
_sale_price (meta)           product.sale_price_raw
_stock_status (meta)         product.stock_status
_yoast_wpseo_title (meta)    product.seo.title
_rank_math_description       product.seo.description
term_id                      category.id
```

This is what allows StoreFuse to: swap out Yoast SEO for RankMath without the frontend noticing; move from WooCommerce to a different commerce backend in the future; upgrade WooCommerce major versions without storefront changes.

---

## Module System

Every feature group is a **module** - a class that extends `StoreFuse_Bridge_Module` and registers its own REST routes. The main plugin class loads only enabled modules.

```php
abstract class StoreFuse_Bridge_Module {
    protected string $namespace = 'storefuse/v1';

    abstract public function register_routes(): void;

    public function is_enabled(): bool {
        return StoreFuse_Bridge_Settings::get("module_{$this->id}_enabled", true);
    }
}
```

Modules are registered in `StoreFuse_Bridge` main class:

```php
$modules = [
    new StoreFuse_Bridge_Module_Status(),
    new StoreFuse_Bridge_Module_Settings(),
    new StoreFuse_Bridge_Module_Products(),
    new StoreFuse_Bridge_Module_Categories(),
    new StoreFuse_Bridge_Module_Search(),
    new StoreFuse_Bridge_Module_Cart(),
    new StoreFuse_Bridge_Module_Checkout(),
    new StoreFuse_Bridge_Module_Content(),
    new StoreFuse_Bridge_Module_Webhooks(),
];

foreach ($modules as $module) {
    if ($module->is_enabled()) {
        add_action('rest_api_init', [$module, 'register_routes']);
    }
}
```

Adding a new module (e.g. for WooCommerce Subscriptions) means creating one new file. Nothing else changes.

---

## Caching Layer

`StoreFuse_Bridge_Cache` wraps WordPress transients with:
- A consistent key prefix (`storefuse_bridge_`)
- A group invalidation system (clear all `products` cache with one call)
- Auto-invalidation hooks wired to WooCommerce save events

```php
// Store
StoreFuse_Bridge_Cache::set('products', $cache_key, $data, HOUR_IN_SECONDS);

// Read
$data = StoreFuse_Bridge_Cache::get('products', $cache_key);

// Invalidate group (called on woocommerce_update_product hook)
StoreFuse_Bridge_Cache::flush_group('products');
```

Group membership is tracked via a version key (the "transient group versioning" pattern). Incrementing the group version key makes all existing keys in the group stale instantly - no DB scan, no `DELETE WHERE LIKE` queries.

**Cache groups, TTLs, and invalidation:**

| Group | TTL | Invalidated by |
|---|---|---|
| `products` | 10 minutes | `woocommerce_update_product`, `save_post_product` |
| `categories` | 1 hour | `woocommerce_update_product_cat`, `edited_product_cat` |
| `settings` | 1 hour | `storefuse_bridge_settings_updated` action |
| `navigation` | 1 hour | `wp_update_nav_menu`, `wp_delete_nav_menu` |
| `homepage` | 15 minutes | `storefuse_bridge_settings_updated`, `woocommerce_update_product` (if featured) |
| `search` | 5 minutes | `woocommerce_update_product` |
| `reviews` | 30 minutes | `comment_post`, `edit_comment` |
| `posts` | 1 hour | `transition_post_status` (publish/update) |

**Cross-group invalidation map** - one event can flush multiple groups:

| Event | Groups Flushed |
|---|---|
| Product saved | `products`, `search`, `homepage` |
| Category saved | `categories`, `products`, `navigation` |
| Settings saved | `settings`, `homepage` |
| Nav menu updated | `settings`, `navigation` |

This is critical at scale. A single product save must not leave stale featured product blocks on the homepage.

---

## Response Builder

All endpoints use `StoreFuse_Bridge_Response` to build consistent responses. Every response is wrapped in a standard envelope that includes a `schema` identifier and `api_version`:

```php
// Success - standard envelope
return StoreFuse_Bridge_Response::success($data, $status = 200);

// Error
return StoreFuse_Bridge_Response::error('product_not_found', 'Product not found', 404);

// Paginated list
return StoreFuse_Bridge_Response::paginated($items, $total, $page, $per_page);
```

The `success()` method wraps data in the standard envelope:

```json
{
  "schema": "storefuse.product.v1",
  "api_version": "1.0.0",
  "data": {
    "...actual response data"
  }
}
```

Why the envelope matters:
- **Frontend compatibility detection** - the storefront reads `schema` to know which response shape it received
- **Debugging** - `api_version` is visible in every response, not just headers
- **Migrations** - when `storefuse/v2` exists, the `schema` field changes to `storefuse.product.v2` and the frontend knows it needs to handle a different shape

Every response includes standard headers:
```
X-StoreFuse-Bridge-Version: 1.0.0
X-StoreFuse-Cache: HIT | MISS
Cache-Control: public, max-age=600
Access-Control-Allow-Origin: *  (or configured origin)
```

---

## Extension Filters

Every module response passes through a WordPress filter before being returned. This is the extension point for third-party plugins.

```php
// In class-module-products.php
$response = apply_filters('storefuse_bridge_product_response', $product_data, $post_id);
return StoreFuse_Bridge_Response::success($response);
```

**All available filters:**

| Filter | Module | Use case |
|---|---|---|
| `storefuse_bridge_settings_response` | Settings | Add custom settings |
| `storefuse_bridge_navigation_response` | Settings | Add custom nav items |
| `storefuse_bridge_homepage_response` | Settings | Add homepage sections |
| `storefuse_bridge_product_response` | Products | Add SEO, ACF, custom fields |
| `storefuse_bridge_products_response` | Products | Filter/sort product list |
| `storefuse_bridge_category_response` | Categories | Add category custom fields |
| `storefuse_bridge_cart_response` | Cart | Add gift wrapping, loyalty points |
| `storefuse_bridge_checkout_response` | Checkout | Add order metadata |
| `storefuse_bridge_review_response` | Content | Add review helpfulness votes |

**Example - Yoast SEO integration:**
```php
add_filter('storefuse_bridge_product_response', function($data, $post_id) {
    if (function_exists('YoastSEO')) {
        $data['seo'] = [
            'title'       => get_post_meta($post_id, '_yoast_wpseo_title', true),
            'description' => get_post_meta($post_id, '_yoast_wpseo_metadesc', true),
            'og_image'    => /* resolve OG image URL */,
        ];
    }
    return $data;
}, 10, 2);
```

This pattern means **StoreFuse Bridge never needs to be modified** to support Yoast, ACF, WPML, or any other plugin. Each integration is its own filter callback - either in a separate integration file in StoreFuse Bridge, or in a third-party plugin.

---

## The Layout API (Future Direction)

The `/homepage` endpoint currently returns a flat configuration object. The architecture already supports evolving this into a **block array** - where WordPress defines the page structure and the storefront renders whatever blocks are returned.

**Current format (v1):**
```json
{
  "hero": { "headline": "...", "cta": "..." },
  "featured_categories": [...]
}
```

**Block array format (future v2 direction):**
```json
[
  { "type": "hero", "props": { "headline": "...", "cta_label": "Shop Now", "cta_href": "/shop" } },
  { "type": "featuredProducts", "props": { "tag": "new-arrivals", "columns": 4 } },
  { "type": "trustBadges", "props": {} },
  { "type": "blogPosts", "props": { "limit": 3, "category": "tips" } }
]
```

This evolution path means:
- Store owners define page layouts from the WP admin (no code deployments)
- The same API can power category pages, landing pages, and seasonal campaign pages
- The storefront becomes a block renderer, not a hardcoded page template

The `storefuse_bridge_homepage_response` filter is already present - a third-party plugin can return a block array through that filter today. The full Layout API is a v2 milestone, not a v1 concern.

---

## WooCommerce Version Resilience

WooCommerce has made several major storage and API changes:
- HPOS (High Performance Order Storage) changed how orders are stored (WC 7.1+)
- WooCommerce Blocks/Store API (`wc/store/v1`) replaced `wc/v2` cart endpoints
- `WC()->cart` API surface has changed across major versions

StoreFuse Bridge handles this with version detection:

```php
class StoreFuse_Bridge_WC_Compat {

    public static function has_hpos(): bool {
        return class_exists('Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    public static function get_order(int $order_id): ?\WC_Order {
        return wc_get_order($order_id); // Works with both HPOS and legacy
    }

    public static function has_store_api(): bool {
        return class_exists('Automattic\WooCommerce\StoreApi\StoreApi');
    }
}
```

All WooCommerce data access routes through these compatibility helpers. When WooCommerce changes internals, only one file needs updating.

---

## API Versioning Strategy

The REST namespace is `storefuse/v1`. When a breaking change is necessary:

1. A `storefuse/v2` namespace is registered alongside v1
2. v1 is deprecated but kept for one major version cycle
3. The storefront's `storefuse.config.ts` has an `api_version` setting to switch

This means: **a WooCommerce plugin update never breaks a live storefront**. The storefront upgrades its API version on its own schedule.

---

## Settings Storage

All plugin settings in one serialized array. One DB read, one memory cache:

```
wp_options key: storefuse_bridge_settings
```

```php
// Read (with in-memory cache)
StoreFuse_Bridge_Settings::get('announcement_bar_text', 'Free shipping over ₹999');

// Write (also fires storefuse_bridge_settings_updated action → flushes settings transient)
StoreFuse_Bridge_Settings::set('announcement_bar_text', $new_value);
```

Full settings schema is documented in [api-reference.md](api-reference.md).

---

## Security Model

| Concern | Approach |
|---|---|
| Public endpoints leak sensitive data | All public endpoints return only storefront-safe data. No order details, no customer PII, no API keys. |
| Cart/checkout manipulation | All cart operations use WC's own validation (stock checks, price validation). The plugin cannot be used to set arbitrary prices. |
| CSRF on cart/checkout | Cart POST endpoints require a WC nonce (`X-WC-Nonce` header) issued by a previous GET /cart request. |
| Admin form CSRF | All admin form submissions verified with `wp_verify_nonce()`. |
| SQL injection | Zero direct SQL queries. All WP/WC API functions used. |
| XSS in admin | All stored strings sanitized on write, escaped on output. |
| CORS | Configurable allowed origins in plugin settings. Defaults to `*` for development, restrict in production. |
| Rate limiting | Not built-in (WordPress has no native rate limiter). Delegate to server-level (nginx, Cloudflare). Documented in deployment guide. |

---

## No Build Step

The plugin is pure PHP + vanilla JavaScript. No Node.js, no Webpack, no Composer. The admin JS is minimal - tab switching and the colour picker using the WP core `wp-color-picker` script. This keeps the plugin simple to install, audit, and maintain.


1. Registers a `storefuse/v1` REST API namespace
2. Reads data from WordPress core, WooCommerce, and plugin-managed options
3. Returns it as JSON through clean, cacheable endpoints
4. Provides a WordPress admin UI to configure the things WP/WC don't natively manage

The plugin has **no frontend output** - it is purely a REST API backend. It does not add any scripts or styles to the WordPress theme. The only UI it adds is the admin settings page.

---

## Folder Structure

```
storefuse-bridge/
│
├── storefuse-bridge.php          ← Plugin entry point, header, bootstrap
│
├── includes/
│   ├── class-plugin.php          ← Main plugin class, wires everything together
│   ├── class-rest-api.php        ← Registers all REST routes
│   ├── class-settings.php        ← Reads/writes plugin options (wp_options)
│   ├── class-admin.php           ← Registers admin menu + settings page
│   └── class-data-resolver.php   ← Reads WP/WC data (logo, menus, currency, etc.)
│
├── admin/
│   └── views/
│       └── settings-page.php     ← Admin UI HTML template
│
├── assets/
│   ├── admin.css                 ← Admin page styles
│   └── admin.js                  ← Admin page JS (tab switching, color picker)
│
├── languages/                    ← i18n .pot file
│
├── docs/                         ← Documentation (not shipped in plugin zip)
│   ├── api-reference.md
│   └── architecture.md
│
├── README.md
├── PLAN.md
└── CHANGELOG.md
```

---

## Class Responsibilities

### `StoreFuse_Bridge` (class-plugin.php)

The single entry class. Instantiated on `plugins_loaded` hook.

- Checks WooCommerce is active - deactivates and shows admin notice if not
- Instantiates and wires all other classes
- Registers activation/deactivation hooks

### `StoreFuse_Bridge_REST_API` (class-rest-api.php)

Registers all REST routes under `storefuse/v1`:

```php
register_rest_route('storefuse/v1', '/status',   [...]);
register_rest_route('storefuse/v1', '/settings', [...]);
register_rest_route('storefuse/v1', '/navigation', [...]);
register_rest_route('storefuse/v1', '/homepage',  [...]);
```

Each route callback calls `StoreFuse_Bridge_Data_Resolver` to build the response array, then returns a `WP_REST_Response`.

All routes have `permission_callback => '__return_true'` (public, read-only).

### `StoreFuse_Bridge_Data_Resolver` (class-data-resolver.php)

Reads data from WordPress and WooCommerce. This is where all the data fetching logic lives, keeping the REST API class thin.

Key methods:

| Method | Data source |
|---|---|
| `get_site_identity()` | `get_bloginfo()`, `get_theme_mod('custom_logo')`, `get_site_icon_url()` |
| `get_store_config()` | `get_woocommerce_currency()`, `get_option('woocommerce_*')`, WC free shipping methods |
| `get_navigation_menus()` | `wp_get_nav_menu_items()` for registered menu locations |
| `get_category_nav()` | `get_terms('product_cat')` - top-level WC categories |
| `get_social_links()` | `StoreFuse_Bridge_Settings::get()` - plugin options |
| `get_trust_badges()` | `StoreFuse_Bridge_Settings::get()` - plugin options |
| `get_hero_config()` | `StoreFuse_Bridge_Settings::get()` - plugin options |
| `get_announcement_bar()` | `StoreFuse_Bridge_Settings::get()` - plugin options |

### `StoreFuse_Bridge_Settings` (class-settings.php)

Manages the plugin's own configuration stored in `wp_options` under the key `storefuse_bridge_settings`.

```php
// Single serialized option - one DB read, cached in memory
$settings = get_option('storefuse_bridge_settings', []);
```

Provides static `get($key, $default)` and `update($key, $value)` helpers.

All values are sanitized on write (`sanitize_text_field`, `esc_url_raw`, `absint`, etc.).

### `StoreFuse_Bridge_Admin` (class-admin.php)

- Adds **WooCommerce → StoreFuse** admin menu item
- Enqueues admin CSS/JS only on the plugin's page
- Handles the settings form POST and calls `StoreFuse_Bridge_Settings::update()`
- Renders the `admin/views/settings-page.php` template

---

## Data Flow

```
WordPress Admin (store owner)
    │  saves settings in
    ▼
wp_options['storefuse_bridge_settings']
    │
    │  read by
    ▼
StoreFuse_Bridge_Data_Resolver
    │  also reads from
    ├── WordPress core  (get_bloginfo, nav menus, site icon)
    └── WooCommerce     (currency, shipping methods, gateways)
    │
    │  returns structured array to
    ▼
StoreFuse_Bridge_REST_API
    │  wraps in WP_REST_Response and returns to
    ▼
StoreFuse Next.js storefront
    │  caches response, makes available via
    ▼
useSiteSettings() hook → Header, Footer, HomePage components
```

---

## WordPress Menu Locations

The plugin registers two nav menu locations in WordPress:

```php
register_nav_menus([
  'storefuse-header' => 'StoreFuse Header Navigation',
  'storefuse-footer' => 'StoreFuse Footer Navigation',
]);
```

Store owners assign their menus to these locations in **Appearance → Menus** (classic) or the Site Editor.

If no menu is assigned, the `/navigation` endpoint returns a sensible default using top-level WooCommerce categories.

---

## Options Schema

All plugin settings are stored as a single serialized array in `wp_options`:

```php
[
  'announcement_bar_enabled'  => true,          // bool
  'announcement_bar_text'     => '...',          // string
  'announcement_bar_bg_color' => '#E85D04',      // hex color string
  'free_shipping_label'       => '...',          // string
  'return_policy_days'        => 7,              // int
  'social_instagram'          => '',             // URL string
  'social_facebook'           => '',             // URL string
  'social_twitter'            => '',             // URL string
  'social_youtube'            => '',             // URL string
  'social_pinterest'          => '',             // URL string
  'social_whatsapp'           => '',             // URL string
  'trust_badges'              => [ ... ],        // array of {icon, title, description}
  'hero_badge_text'           => '...',          // string
  'hero_headline'             => '...',          // string
  'hero_subheadline'          => '...',          // string
  'hero_cta_primary_label'    => 'Shop Now',     // string
  'hero_cta_primary_href'     => '/shop',        // string
  'hero_cta_secondary_label'  => '',             // string
  'hero_cta_secondary_href'   => '',             // string
  'hero_image_id'             => 0,              // attachment ID
  'featured_categories'       => [ ... ],        // array of category slugs (ordered)
]
```

---

## Security

- All REST endpoints are public and read-only. They expose no sensitive data (no user data, no order data, no API keys).
- All admin form inputs are sanitized using WordPress core sanitization functions before saving to `wp_options`.
- The plugin uses `wp_verify_nonce()` on all admin form submissions.
- No direct database queries - all data access goes through WordPress and WooCommerce APIs.

---

## No Build Step

The plugin is pure PHP + vanilla JS. No Node.js, no Webpack, no Composer (PHP dependencies). This keeps it simple to install and maintain. The admin JS is a small plain script for tab switching and the color picker (using the native WordPress color picker from WordPress core).
