# StoreFuse Bridge - Development Plan

**Status**: Planning
**Last Updated**: May 2026

---

## Vision

StoreFuse Bridge is the **headless application layer WooCommerce never had officially**. It is a complete API layer that sits between WordPress/WooCommerce and any headless storefront - normalising WooCommerce's complex, admin-oriented REST API into a clean, storefront-optimised API that any frontend can consume with minimal requests.

The goal is not "Next.js starter for WooCommerce." The goal is **headless commerce platform for WordPress/WooCommerce** - a system where the frontend never depends on WooCommerce internals and where the store owner never needs to configure API keys or understand REST.

### Core Principles

1. **StoreFuse Bridge is the exclusive API** - The StoreFuse storefront talks to one namespace only: `/storefuse/v1/*`. It never calls Woo REST (`wc/v3`), Woo Store API (`wc/store/v1`), or WPGraphQL directly. This means: the frontend never breaks on WooCommerce updates, data shape is always in StoreFuse's control, and responses can be optimised and cached without touching the storefront.

2. **No WordPress/WooCommerce concepts leak to the frontend** - The frontend should never see `post_meta`, `wp_options` keys, taxonomy internals, ACF field names, or raw Woo REST objects. The plugin normalises everything. Example: the frontend receives `{ "seo": { "title": "...", "description": "..." } }` - never `{ "_rank_math_title": "..." }`.

3. **Module system** - Every feature group (products, cart, checkout, content) is a module that can be enabled or disabled independently. New ecommerce features are added as new modules without touching existing ones.

4. **Versioned API** - The namespace is versioned (`storefuse/v1`). When a breaking change is needed, `storefuse/v2` is added alongside v1. Existing storefronts never break on plugin updates.

5. **Storefront-first, not admin-first** - Standard WC REST API endpoints are designed for admin tools. StoreFuse Bridge endpoints are designed for rendering pages: fewer requests, pre-aggregated data, no auth required for public data.

6. **Internal auth, public output** - Cart, checkout, and user features are proxied through the plugin using WooCommerce's own session/nonce system. The storefront never handles consumer keys or secrets.

7. **Cached by default** - Product and category data is cached via WordPress transients. Cache is auto-invalidated by WooCommerce save hooks. The storefront fetches live data, the plugin serves it fast.

8. **Extension hooks everywhere** - Every endpoint response passes through a WordPress filter. Third-party plugins (ACF, Yoast, WPML, etc.) can extend any response without modifying plugin files.

9. **WooCommerce version resilience** - All WooCommerce data access goes through WC functions and hooks, not direct DB queries. Plugin works across WC 7.x, 8.x, 9.x without modification.

10. **Schema/version envelope** - Every response carries a `schema` identifier and `api_version`. This makes client-side compatibility detection trivial and simplifies debugging across plugin versions.

11. **StoreFuse contracts are the source of truth** - Before building any endpoint, the StoreFuse type for that resource is defined. `Product`, `Order`, `Customer`, `Cart`, `Address` are stable contracts. WooCommerce data maps into them. The frontend, mobile apps, and any future clients all consume the same contract. WooCommerce internals are never exposed.

12. **WordPress auth, not custom auth** - Authentication uses WordPress native cookies (`wp_signon`, `wp_set_auth_cookie`) and WooCommerce sessions. No JWT-first architecture, no Firebase, no Auth0. HTTP-only cookies only - never localStorage tokens. This keeps full compatibility with the WooCommerce plugin ecosystem (memberships, subscriptions, wishlists, loyalty plugins).

13. **Public cache and session cache are strictly separate** - Public endpoints (`/products`, `/categories`, `/homepage`) return `Cache-Control: public, max-age=N` and are safe for CDN and shared caching. Session endpoints (`/cart`, `/account`, `/orders`, `/wishlist`) return `Cache-Control: no-store`. Mixing these causes cache leaks, wrong cart data for different users, and auth failures. This is enforced at the infrastructure level - not left to individual modules.

14. **Cursor pagination is architecturally supported** - v1 uses `page` + `per_page` offset pagination. This is correct for v1 catalog sizes. The response `meta` envelope is designed to be extended: `cursor=` support can be added per endpoint without breaking existing `page=` consumers. Modules must never hardcode offset-only pagination at the query layer.

15. **Commerce focus, not CMS expansion** - StoreFuse Bridge is a commerce storefront API. Posts and reviews exist because stores need content and social proof to sell products. StoreFuse Bridge will not become a generic WordPress CMS API, a headless content platform, or a page builder backend. Every feature request must pass the test: does a customer need this to complete a purchase?

---

## Full API Surface (Target)

```
# Health
GET  /storefuse/v1/status

# Site configuration (public)
GET  /storefuse/v1/settings
GET  /storefuse/v1/navigation
GET  /storefuse/v1/homepage

# Catalogue (public, cached)
GET  /storefuse/v1/products
GET  /storefuse/v1/products/{slug}
POST /storefuse/v1/products/{slug}/notify      (back-in-stock alert signup)
GET  /storefuse/v1/categories
GET  /storefuse/v1/categories/{slug}
GET  /storefuse/v1/attributes                  (all attributes + terms for filter sidebar)
GET  /storefuse/v1/tags                        (all product tags)
GET  /storefuse/v1/search?q=

# Authentication
POST /storefuse/v1/auth/register
POST /storefuse/v1/auth/login
POST /storefuse/v1/auth/logout
GET  /storefuse/v1/auth/me
POST /storefuse/v1/auth/forgot-password
POST /storefuse/v1/auth/reset-password

# Customer account (requires login)
GET  /storefuse/v1/account
PUT  /storefuse/v1/account
POST /storefuse/v1/account/change-password

# Orders (requires login)
GET  /storefuse/v1/orders
GET  /storefuse/v1/orders/{id}
POST /storefuse/v1/orders/{id}/cancel
POST /storefuse/v1/orders/{id}/reorder
POST /storefuse/v1/orders/{id}/return-request
GET  /storefuse/v1/orders/{id}/tracking
GET  /storefuse/v1/orders/{id}/invoice

# Addresses (requires login)
GET  /storefuse/v1/addresses
PUT  /storefuse/v1/addresses/billing
PUT  /storefuse/v1/addresses/shipping

# Wishlist (requires login)
GET  /storefuse/v1/wishlist
POST /storefuse/v1/wishlist/add
DELETE /storefuse/v1/wishlist/remove

# Cart (session-based, works guest + logged in)
GET    /storefuse/v1/cart
POST   /storefuse/v1/cart/add
PUT    /storefuse/v1/cart/update
DELETE /storefuse/v1/cart/remove
POST   /storefuse/v1/cart/coupon
DELETE /storefuse/v1/cart/coupon

# Checkout
GET  /storefuse/v1/checkout/config
GET  /storefuse/v1/checkout/payment-methods
GET  /storefuse/v1/checkout/shipping-methods
POST /storefuse/v1/checkout
POST /storefuse/v1/checkout/redirect-url
GET  /storefuse/v1/orders/{key}    (order confirmation by order key, public)

# Content (public)
GET  /storefuse/v1/reviews?product_id=
POST /storefuse/v1/reviews
GET  /storefuse/v1/posts
GET  /storefuse/v1/posts/{slug}

# Utilities (public)
GET  /storefuse/v1/utils/countries
GET  /storefuse/v1/utils/pincode/{pincode}      (pincode -> city/state lookup for India)

# Downloads (requires login, digital products)
GET  /storefuse/v1/downloads
```

Each group is a module. Build order follows phases below.

---

## Phase 1: Plugin Foundation

**Goal**: A working plugin shell that activates cleanly, registers the versioned API namespace, and passes the health check.

- [ ] Create `storefuse-bridge.php` plugin entry file
  - [ ] WordPress plugin header (name, version, author, license, requires WP/WC versions)
  - [ ] Activation hook - verify WooCommerce is active, show notice if not
  - [ ] Deactivation hook - flush any cached transients
  - [ ] Uninstall hook - clean up `wp_options` entries
  - [ ] PSR-4-style class autoloader (no Composer dependency, simple `spl_autoload_register`)
- [ ] Create `StoreFuse_Bridge` main plugin class
  - [ ] Instantiated on `plugins_loaded` hook
  - [ ] Initialises module system - loads only enabled modules
  - [ ] Defines plugin constants (`STOREFUSE_BRIDGE_VERSION`, `STOREFUSE_BRIDGE_PATH`, etc.)
- [ ] Create `StoreFuse_Bridge_Module` base abstract class
  - [ ] `register_routes()` method - each module implements this
  - [ ] `is_enabled()` method - checks plugin settings
- [ ] Create `StoreFuse_Bridge_Auth` class (`class-auth.php`)
  - [ ] `validate_nonce(WP_REST_Request $request): bool` - validates `X-WC-Nonce` header
  - [ ] `validate_cart_session(): bool` - confirms a WC session exists for the request
  - [ ] `get_permission_callback(string $type): callable` - returns the correct permission_callback per endpoint type (`public`, `cart`, `checkout`)
  - [ ] Future-ready: internal hook point for JWT/token auth without changing module code
  - [ ] Used by: Cart module, Checkout module, Review POST. Never duplicated across modules.
- [ ] Create `StoreFuse_Bridge_Permissions` class (`class-permissions.php`)
  - [ ] `require_login(WP_REST_Request $request): bool|WP_REST_Response` - returns error response if user is not logged in, used as `permission_callback` on all customer endpoints
  - [ ] `verify_nonce(WP_REST_Request $request): bool` - verifies `X-WP-Nonce` header for write operations
  - [ ] `can_manage_order(int $order_id): bool` - confirms the current user owns the order (prevents customer A reading customer B orders)
  - [ ] This class keeps module route definitions clean. No auth logic inline inside callback handlers.
- [ ] Create `StoreFuse_Bridge_Session` class (`class-session.php`)
  - [ ] Manages WC session lifecycle: initialise, read, write, destroy
  - [ ] `merge_guest_cart_after_login(int $user_id): void` - called on `wp_login` hook. Merges any items in the guest session cart into the user's saved cart. This is a critical ecommerce requirement: user adds to cart as guest, logs in, and items must not disappear.
  - [ ] `get_cart_token(): string` - returns a session identifier safe to send to the frontend
  - [ ] `set_cart_token_header(WP_REST_Response $response): WP_REST_Response` - adds `X-StoreFuse-Cart-Token` to response headers for stateless clients (mobile apps)
- [ ] Create `StoreFuse_Bridge_Format` class (`class-format.php`)
  - [ ] `price(float $amount): array` - returns `{ "raw": 999.00, "formatted": "Rs. 999.00", "currency": "INR", "symbol": "Rs." }` using WC currency settings. **Never return a price string directly. Every price value in every response uses this exact shape.**
  - [ ] `image(int|null $attachment_id): array|null` - returns `{ "url": "...", "alt": "...", "width": 1200, "height": 1200, "srcset": ["url @300w", "url @600w"] }`. **Never return a raw URL string. Every image in every response uses this exact shape.** Returns placeholder shape when attachment_id is null.
  - [ ] `product(WC_Product $product): array` - full normalised product shape
  - [ ] `category(WP_Term $term): array` - normalised category shape
  - [ ] `date(string $date): string` - WP date string to ISO 8601
  - [ ] All formatting logic lives here. No duplicate price or image logic in modules.
- [ ] Create `StoreFuse_Bridge_Errors` class (`class-errors.php`)
  - [ ] Static factory methods for every known error: `product_not_found()`, `category_not_found()`, `cart_item_not_found()`, `coupon_invalid()`, `coupon_expired()`, `out_of_stock()`, `checkout_failed()`, `invalid_nonce()`, `validation_error(string $message)`
  - [ ] Each method returns a consistent `WP_REST_Response` with a fixed `code`, `message`, HTTP `status` code, and HTTP status. Error body shape: `{ "code": "product_not_found", "message": "...", "status": 404 }`. The `status` field is included in the body (not just the HTTP status code) so clients that don't inspect status codes still get the right error context.
  - [ ] Frontend never gets inconsistent error shapes. One place to update error copy.
- [ ] Create `StoreFuse_Bridge_Request_Context` class (`class-request-context.php`)
  - [ ] Resolved once per request, shared across all modules in that request
  - [ ] Properties: `user` (WP_User|null), `session` (WC_Session|null), `currency` (string ISO 4217), `language` (string ISO 639), `device` (string: mobile|desktop|unknown), `cart_token` (string)
  - [ ] `from_request(WP_REST_Request $request): self` static factory method
  - [ ] Eliminates repeated `is_user_logged_in()`, `WC()->session`, `WC()->cart` calls scattered across modules. Modules receive context, they do not query it.
- [ ] Register `GET /storefuse/v1/status` endpoint
  - Returns: plugin version, WP version, WC version, PHP version, active modules list
  - Also returns a `features` capability map:
    ```json
    {
      "features": {
        "hpos": true,
        "store_api": true,
        "headless_checkout": false,
        "subscriptions": false,
        "wpml": false,
        "yoast_seo": true,
        "rank_math": false
      }
    }
    ```
  - Frontend reads this at runtime to adjust behaviour without hardcoded capability checks
  - Each key is detected via `class_exists()` or `is_plugin_active()` at request time
- [ ] Confirm plugin activates on XAMPP local WordPress

**Deliverable**: Plugin activates. `/storefuse/v1/status` returns 200 with version info and feature flags.

---

## Phase 2: Settings Module

**Goal**: Expose all site identity and store configuration that is currently hardcoded in the StoreFuse storefront.

**Endpoints**: `GET /settings`, `GET /navigation`, read-only

- [ ] Read site identity from WordPress core
  - Site name, tagline, URL, admin email
  - Logo URL (resolve `get_theme_mod('custom_logo')` attachment ID to full URL)
  - Favicon URL (`get_site_icon_url()`)
- [ ] Read WooCommerce store settings
  - Currency code, symbol, position, decimal/thousand separators, decimals
  - Free shipping threshold (read active WC free shipping method settings)
  - COD enabled/disabled (check active payment gateways)
- [ ] Read plugin-managed settings from `wp_options`
  - Announcement bar text, enabled flag, background colour
  - Trust badges (array)
  - Social media links
  - Return policy days
- [ ] Navigation: read WordPress nav menus from two registered locations
  - `storefuse-header` - header nav
  - `storefuse-footer` - footer links
  - Fallback: auto-generate from top-level WooCommerce categories
- [ ] Register menu locations with WordPress (`register_nav_menus`)
- [ ] Add extension filters:
  - `storefuse_bridge_settings_response` - filter the full settings response
  - `storefuse_bridge_navigation_response` - filter nav response
- [ ] Cache navigation response with 1-hour transient, invalidate on menu save

**Deliverable**: StoreFuse storefront can replace hardcoded logo, favicon, site name, nav, currency, announcement bar.

---

## Phase 3: Admin Settings Pages

**Goal**: A complete admin UI where store owners control every piece of storefront content and configuration from WordPress admin - no developer or code changes needed.

---

### Admin Menu Structure

The plugin registers one top-level menu item and multiple sub-pages under WooCommerce. Every sub-page has its own URL slug so they can be linked to directly.

```
WooCommerce
  |
  +-- StoreFuse Bridge                     /wp-admin/admin.php?page=storefuse-bridge
        |
        +-- Dashboard                      /wp-admin/admin.php?page=storefuse-bridge
        +-- General Settings               /wp-admin/admin.php?page=storefuse-bridge-general
        +-- Homepage                       /wp-admin/admin.php?page=storefuse-bridge-homepage
        +-- Navigation                     /wp-admin/admin.php?page=storefuse-bridge-navigation
        +-- Social & Trust                 /wp-admin/admin.php?page=storefuse-bridge-social
        +-- Checkout                       /wp-admin/admin.php?page=storefuse-bridge-checkout
        +-- Advanced                       /wp-admin/admin.php?page=storefuse-bridge-advanced
```

Each sub-page is a separate `add_submenu_page()` call. This gives each page its own browser URL so store owners can bookmark individual sections and links in any documentation point directly to the right page.

---

### Page 1: Dashboard (storefuse-bridge)

The landing page. Shows a status overview - no form, read-only.

- [ ] Connection status: plugin version, WP version, WC version, PHP version
- [ ] Module status table: each module (settings, products, cart, checkout, etc.) shown as enabled/disabled with a direct link to the Advanced page to change it
- [ ] Quick links: one-click links to each sub-page
- [ ] API base URL displayed with a copy button (e.g. `https://yourstore.com/wp-json/storefuse/v1`)
- [ ] Last cache flush time
- [ ] Link to the full API reference documentation

---

### Page 2: General Settings (storefuse-bridge-general)

Site-level settings that the storefront header, footer, and announcement bar use.

**Announcement Bar**
- [ ] Enabled/disabled toggle
- [ ] Bar text (plain text, no HTML)
- [ ] Background color (WP color picker)
- [ ] Link URL (optional - makes the bar clickable)

**Store Policies**
- [ ] Return policy days (number input - used in trust badges and footer)
- [ ] Free shipping threshold label (text - e.g. "Free shipping on orders above 999")
- [ ] Free shipping threshold amount (number - used in cart to show progress bar)

**Site Identity** (read-only display, edited in WP Customizer)
- [ ] Show current logo URL with a link to WP Customizer > Site Identity
- [ ] Show current favicon URL with a link to WP Customizer > Site Identity
- [ ] Note: "To change logo or favicon, go to Appearance > Customize > Site Identity"

---

### Page 3: Homepage (storefuse-bridge-homepage)

Every section of the storefront home page is configurable here. No hardcoded content in the Next.js app.

**Hero Section**
- [ ] Badge text - small label above the headline (e.g. "New Collection 2026")
- [ ] Main headline (plain text)
- [ ] Highlighted word(s) in headline - the word(s) the theme renders in a different color
- [ ] Sub-headline / description text
- [ ] Primary CTA button label
- [ ] Primary CTA button URL
- [ ] Secondary CTA button label
- [ ] Secondary CTA button URL
- [ ] Hero image (WP media upload - stores attachment ID, returns full URL via API)
- [ ] Rating display text (e.g. "4.8/5 from 2,400 reviews")
- [ ] Shipping badge text (e.g. "Free shipping over 999")

**Featured Categories Row**
- [ ] Up to 6 category slots, each with:
  - WooCommerce category selector (dropdown populated from WC categories)
  - Display label override (leave blank to use category name)
  - Icon or emoji (plain text field - user types whatever character they want)
  - Background color class or hex color
- [ ] These appear in the storefront as the clickable category quick-links row

**Best Sellers / Featured Products Section**
- [ ] Section heading text (e.g. "Best Sellers")
- [ ] Section sub-heading text (optional)
- [ ] Product selection method: radio button
  - Auto: use WooCommerce "featured" flag on products
  - Auto: use best-selling products (by sales count)
  - Manual: pick specific products by ID or SKU (text input, comma-separated)
- [ ] Number of products to show (4, 8, 12)

**New Arrivals Section**
- [ ] Section heading text
- [ ] Number of products to show
- [ ] Sort by: newest first / newest in a specific category

**Promotional Banner (optional)**
- [ ] Enabled/disabled toggle
- [ ] Banner headline
- [ ] Banner description
- [ ] CTA label and URL
- [ ] Background color

---

### Page 4: Navigation (storefuse-bridge-navigation)

- [ ] Instruction panel: "Header and footer nav links are managed in Appearance > Menus. Assign a menu to the storefuse-header or storefuse-footer location."
- [ ] Direct link button to Appearance > Menus (opens in same tab)
- [ ] Live preview: shows the current items in the `storefuse-header` menu, read-only
- [ ] Live preview: shows the current items in the `storefuse-footer` menu, read-only
- [ ] If no menu is assigned to a location, show a warning with a link to create one
- [ ] "Flush Navigation Cache" button - invalidates only the navigation transient

---

### Page 5: Social & Trust (storefuse-bridge-social)

**Social Media Links**
- [ ] Instagram URL
- [ ] Facebook URL
- [ ] Twitter / X URL
- [ ] YouTube URL
- [ ] Pinterest URL
- [ ] WhatsApp number (phone number field - plugin builds the wa.me link)

**Trust Badges** (up to 4 badges shown in the storefront trust bar)
- [ ] Badge 1: Icon (plain text), Title, Description
- [ ] Badge 2: Icon (plain text), Title, Description
- [ ] Badge 3: Icon (plain text), Title, Description
- [ ] Badge 4: Icon (plain text), Title, Description
- [ ] Each badge has an enabled/disabled toggle

---

### Page 6: Checkout (storefuse-bridge-checkout)

**Checkout Mode**
- [ ] Radio button: two options
  - **Redirect (recommended)** - After cart, user goes to the native WooCommerce checkout page. WooCommerce handles the entire payment UI. Works with every payment gateway with zero extra config.
  - **Headless** - Checkout renders inside the Next.js storefront. Full branded experience. Requires per-payment-gateway configuration.
  - Default: Redirect
  - Stored as: `checkout_mode` = `redirect` or `headless`
  - When saved, the plugin exposes this via `GET /storefuse/v1/checkout/config` - the Next.js storefront reads the mode at runtime and renders the correct flow automatically

**Redirect Mode Settings** (shown when Redirect is selected)
- [ ] Custom redirect label (text shown on the "Go to Checkout" button in the Next.js cart)
- [ ] WooCommerce checkout page URL override (auto-detected but can be set manually)

**Headless Mode Settings** (shown when Headless is selected)
- [ ] Warning notice: "Headless checkout requires per-gateway setup. Read the documentation before enabling."
- [ ] Link to checkout gateway documentation
- [ ] Enabled payment gateways list (read-only, pulled from WooCommerce active gateways, shows which ones have headless support implemented)

---

### Page 7: Advanced (storefuse-bridge-advanced)

**Module Enable/Disable**
- [ ] Toggle for each module: Settings, Products, Categories, Search, Cart, Checkout, Content, Webhooks
- [ ] Each toggle shows the affected API endpoints so the user knows what they are disabling

**Cache Control**
- [ ] "Flush All Cache" button - clears every StoreFuse Bridge transient
- [ ] Per-group flush buttons: Products, Settings, Navigation, Homepage, Search, Reviews
- [ ] Last flush time for each group (read-only)

**Storefront Connection (for Webhooks / ISR)**
- [ ] Storefront URL (the Next.js app URL - used for ISR revalidation webhooks)
- [ ] Revalidation secret key (shared secret - also entered in the Next.js app)
- [ ] "Test Connection" button - sends a test webhook and shows the response

**CORS**
- [ ] Allowed origins (textarea, one URL per line)
- [ ] Default: `*` (open) - with a notice to restrict in production

---

### Implementation Notes

- [ ] All sub-pages use a shared `StoreFuse_Bridge_Admin` class
- [ ] Each page's form posts to `admin-post.php` with its own action name (e.g. `storefuse_bridge_save_homepage`)
- [ ] All saves verified with `check_admin_referer()` and `current_user_can('manage_options')`
- [ ] All string inputs: `sanitize_text_field()`
- [ ] All URL inputs: `esc_url_raw()`
- [ ] All number inputs: `absint()` or `floatval()`
- [ ] All HTML-allowed inputs (none currently): `wp_kses_post()`
- [ ] Admin JS is vanilla JS only - no jQuery dependency. Handles: tab switching, color picker init, toggle show/hide for conditional fields (e.g. headless settings only visible when headless mode is selected)
- [ ] All settings stored in one serialized array: `wp_options['storefuse_bridge_settings']`
- [ ] One DB read on admin page load, cached in a static variable for the request lifetime

**Deliverable**: Every storefront content element (hero, featured products, badges, social links, nav, checkout mode) is configurable from WP admin. No developer or code deployment needed for content changes.

---

## Phase 4: Products Module

**Goal**: A storefront-optimised product and category API that returns clean, pre-normalised data matching StoreFuse types. Cached server-side so the storefront never waits on slow WC queries.

**Endpoints**: `GET /products`, `GET /products/{slug}`, `GET /categories`, `GET /categories/{slug}`

- [ ] `GET /products` - list products
  - Query params: `per_page`, `page`, `category`, `tag`, `orderby`, `order`, `on_sale`, `featured`, `search`, `min_price`, `max_price`
  - Response: normalised StoreFuse `Product[]` type + pagination meta
  - Cached: 10-minute transient, keyed by query hash
  - Cache invalidated: on `woocommerce_update_product` and `save_post_product` hooks
- [ ] `GET /products/{slug}` - single product
  - Response: full product + `related_products[]` (4 items) + `breadcrumb[]`
  - This is the "one request renders the page" endpoint
  - Cached: 10-minute transient per slug
- [ ] `GET /categories` - full category tree
  - Response: hierarchical category list with image URLs, product counts
  - Includes top-level and child categories
  - Cached: 1-hour transient
  - Cache invalidated: on `woocommerce_update_product_cat`
- [ ] `GET /categories/{slug}` - single category + its products
  - Response: category data + first page of products
  - Avoids the storefront needing two API calls to render a category page
- [ ] Response normalisation:
  - All image URLs returned as full absolute URLs (no relative paths)
  - All prices returned as both raw number and formatted string (e.g. `"499"` or `"$49.99"`)
  - All dates normalised to ISO 8601
- [ ] Extension filters:
  - `storefuse_bridge_product_response` - modify single product response
  - `storefuse_bridge_products_response` - modify product list response
  - `storefuse_bridge_category_response` - modify category response

**Deliverable**: Storefront can fetch products and categories from one endpoint per page. Response matches StoreFuse types exactly.

---

## Phase 5: Search Module

**Goal**: Fast, relevant product search that works across product names, descriptions, SKUs, and categories.

**Endpoint**: `GET /search?q=`

- [ ] `GET /search?q={term}` - search products
  - Uses WooCommerce product search (respects stock status, visibility)
  - Query params: `q` (required), `per_page`, `page`
  - Response: `SearchResult[]` matching StoreFuse type
  - Each result: id, name, slug, price (formatted), image, categories, in_stock
- [ ] Optional: integrate with WooCommerce Product Search plugin if active
- [ ] Debounce protection: minimum 2 characters required
- [ ] Cached: 5-minute transient per search term

**Deliverable**: Search endpoint returns fast, relevant results matching StoreFuse `SearchResult` type.

---

## Phase 6: Cart Module

**Goal**: Full cart management proxied through WordPress, so the storefront never needs consumer keys for cart operations. Uses WooCommerce's own session system.

**Endpoints**: `GET /cart`, `POST /cart/add`, `PUT /cart/update`, `DELETE /cart/remove`, `POST /cart/coupon`, `DELETE /cart/coupon`

- [ ] Use WooCommerce `WC()->cart` internally - do not bypass WC cart logic
  - This ensures stock checks, tax calculation, shipping, coupons all work automatically
- [ ] Session management:
  - Read/write WC session via `WC()->session`
  - Use `wc-cart-nonce` cookie for cart identification
  - Return `cart_token` in response headers for stateless clients- [ ] Rate limit action hook on all write endpoints (add, update, remove, coupon):
    - `do_action('storefuse_bridge_rate_limit_hit', $request)`
    - This action fires when a write request is received. The plugin does not rate-limit itself - that belongs at the server layer (nginx, Cloudflare). But firing this action lets security plugins, analytics, and fail2ban integrations react to high-frequency requests without needing to patch plugin files.- [ ] `GET /cart` - return current cart
  - Items (with product name, image, price, quantity, subtotal)
  - Totals (subtotal, shipping, tax, discount, total)
  - Applied coupons
  - Available shipping methods
- [ ] `POST /cart/add` - add item
  - Body: `{ product_id, quantity, variation_id? }`
  - Validates stock before adding
  - Returns updated cart
- [ ] `PUT /cart/update` - update quantity
  - Body: `{ cart_item_key, quantity }`
  - Returns updated cart
- [ ] `DELETE /cart/remove` - remove item
  - Body: `{ cart_item_key }`
  - Returns updated cart
- [ ] `POST /cart/coupon` - apply coupon
  - Body: `{ coupon_code }`
  - Returns error if coupon invalid/expired
- [ ] `DELETE /cart/coupon` - remove coupon
  - Body: `{ coupon_code }`
- [ ] Extension filter: `storefuse_bridge_cart_response`

**Deliverable**: Full cart management from the Next.js storefront without any consumer keys. Cart persists across sessions.

---

## Phase 7: Auth Module

**Goal**: A complete authentication system that uses WordPress native auth and WooCommerce sessions. No JWT, no custom token system, no Firebase. The frontend never talks directly to WordPress auth APIs.

**Auth strategy**: WordPress auth cookies (`wp_signon`, `wp_set_auth_cookie`) + HTTP-only cookies. This keeps full compatibility with the WooCommerce plugin ecosystem - memberships, subscriptions, wishlists, loyalty plugins, affiliate plugins all rely on the WP user system. Changing auth breaks them.

**What to avoid**:
- JWT-first architecture - painful with WC sessions, annoying token refresh handling
- localStorage auth tokens - XSS risk, not needed when HTTP-only cookies work
- Firebase, Auth0, Clerk - breaks the WooCommerce ecosystem compatibility
- Custom password reset logic - use WP core (`retrieve_password()`, `reset_password()`) internally

**Endpoints**: `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`, `POST /auth/forgot-password`, `POST /auth/reset-password`

- [ ] `POST /auth/register` - create a new customer account
  - Body: `{ "email": "...", "password": "...", "first_name": "...", "last_name": "..." }`
  - Internally: `wc_create_new_customer()` (creates WP user + WC customer meta in one call)
  - After register: call `wp_set_auth_cookie()` to auto-login - critical for conversion rate
  - Returns: `{ "user": { "id", "name", "email" } }` - no token, auth is in the HTTP-only cookie
  - Extension filter: `storefuse_bridge_register_response`
- [ ] `POST /auth/login` - authenticate an existing customer
  - Body: `{ "email": "...", "password": "..." }`
  - Internally: `wp_signon()` then `wp_set_auth_cookie()`
  - On success: fire `storefuse_bridge_after_login` action so `StoreFuse_Bridge_Session::merge_guest_cart_after_login()` can run - guest cart items merge into logged-in cart
  - Returns: `{ "user": { "id", "name", "email" } }` - cookie set by server, browser stores it automatically
  - Extension filter: `storefuse_bridge_login_response`
- [ ] `POST /auth/logout` - end the session
  - Internally: `wp_logout()`, destroy WC session
  - Requires: `X-WP-Nonce` header (CSRF protection on all state-changing auth endpoints)
- [ ] `GET /auth/me` - bootstrap check for the frontend
  - Called on storefront startup to determine if user is logged in
  - If logged in: `{ "logged_in": true, "user": { "id", "name", "email", "avatar_url" } }`
  - If not logged in: `{ "logged_in": false }`
  - This powers: account menu, orders link, wishlist, address book, personalised content
  - No nonce required (read-only, safe)
- [ ] `POST /auth/forgot-password` - trigger password reset email
  - Body: `{ "email": "..." }`
  - Internally: `retrieve_password($login)` - WordPress sends the email using its own template
  - Do NOT write custom email logic. WP core handles it.
  - Returns: `{ "sent": true }` regardless of whether email exists (security - prevents user enumeration)
- [ ] `POST /auth/reset-password` - complete the password reset
  - Body: `{ "key": "...", "login": "...", "password": "..." }`
  - Internally: `check_password_reset_key()` to validate, then `reset_password()`
  - Do NOT write custom reset logic. WP core handles it.
- [ ] Guest cart merge after login:
  - `StoreFuse_Bridge_Session::merge_guest_cart_after_login()` is hooked to `wp_login` WordPress action
  - Reads any items in the current (guest) WC session cart
  - Loads the user's saved cart
  - Merges items, resolves duplicates by adding quantities
  - This is a critical ecommerce requirement. Losing a guest cart on login causes abandoned purchases.
- [ ] CSRF protection on all write endpoints (`login`, `logout`, `register`, `forgot-password`, `reset-password`) via `X-WP-Nonce` header
- [ ] Extension filter: `storefuse_bridge_auth_response` - for social login plugin integration (Nextend Social Login, miniOrange, etc.)

**Deliverable**: Complete customer auth powered by WordPress. HTTP-only cookie session. Guest cart preserved through login. Frontend only calls `/storefuse/v1/auth/*`.

---

## Phase 8: Customer APIs

**Goal**: A complete customer account API surface for the logged-in experience. Orders, addresses, account details, wishlist, and digital downloads.

**Auth requirement**: All endpoints in this phase use `StoreFuse_Bridge_Permissions::require_login()` as `permission_callback`. Unauthenticated requests get a 401.

**Endpoints**: `GET /account`, `PUT /account`, `POST /account/change-password`, `GET /orders`, `GET /orders/{id}`, `POST /orders/{id}/cancel`, `GET /orders/{id}/tracking`, `GET /addresses`, `PUT /addresses/billing`, `PUT /addresses/shipping`, `GET /wishlist`, `POST /wishlist/add`, `DELETE /wishlist/remove`, `GET /downloads`

- [ ] `GET /account` - return the current user's profile
  - Returns: `{ "id", "email", "first_name", "last_name", "avatar_url", "date_registered" }`
- [ ] `PUT /account` - update profile (name, display name)
  - Body: `{ "first_name": "...", "last_name": "..." }`
  - Internally: `wp_update_user()`
- [ ] `POST /account/change-password` - change password for logged-in user
  - Body: `{ "current_password": "...", "new_password": "..." }`
  - Validates current password before changing. Returns 422 if current password is wrong.
- [ ] `GET /orders` - list all orders for the current customer
  - Response: normalised `Order[]` - id, number, status, date, total, item count, tracking status
  - Internally: `wc_get_orders([ 'customer' => get_current_user_id() ])`
  - Uses HPOS-safe access via `StoreFuse_Bridge_WC_Compat::get_order()`
  - Pagination: `per_page`, `page` params
- [ ] `GET /orders/{id}` - full order detail
  - Verify ownership via `StoreFuse_Bridge_Permissions::can_manage_order($id)` before returning - customer A must not read customer B orders
  - Returns full normalised Order: items (with image, name, qty, subtotal), totals breakdown, billing/shipping address, payment method, tracking
  - This is the order confirmation page and order history detail page data in one request
- [ ] `POST /orders/{id}/cancel` - cancel a cancellable order
  - Only allowed when order status is `pending` or `on-hold`
  - Internally: `$order->update_status('cancelled')`
- [ ] `GET /orders/{id}/tracking` - shipment tracking
  - Returns carrier and tracking number if set (from order meta - compatible with Shipment Tracking plugin)
  - Returns `{ "available": false }` if no tracking set yet
- [ ] `GET /addresses` - return saved billing and shipping addresses
- [ ] `PUT /addresses/billing` - update billing address
  - Internally: `update_user_meta($user_id, 'billing_*', $value)` for each field
- [ ] `PUT /addresses/shipping` - update shipping address
- [ ] `GET /wishlist` - return saved wishlist items
  - Stored in user meta as `storefuse_bridge_wishlist` (array of product IDs)
  - Returns normalised product objects (not just IDs)
  - Extension filter: `storefuse_bridge_wishlist_response` for WooCommerce Wishlist plugin compatibility
- [ ] `POST /wishlist/add` - add product to wishlist
  - Body: `{ "product_id": 123 }`
- [ ] `DELETE /wishlist/remove` - remove from wishlist
  - Body: `{ "product_id": 123 }`
- [ ] `GET /downloads` - list digital product downloads for the current customer
  - Internally: `wc_get_customer_available_downloads($user_id)`
  - Returns: product name, download file name, download URL, expires_at, download count remaining
- [ ] Extension filter: `storefuse_bridge_order_response` - for shipment tracking plugins, loyalty points, etc.

**Deliverable**: Full logged-in customer experience. Orders, addresses, wishlist, downloads. Every endpoint returns a normalised StoreFuse type - never raw WooCommerce data.

---

## Phase 9: Checkout Module

**Goal**: Implement both checkout modes so the store owner can choose from the admin settings page which experience their customers get. The Next.js storefront reads the chosen mode from the plugin and renders accordingly - no storefront code changes needed when switching modes.

**Endpoints**: `GET /checkout/config`, `GET /checkout/payment-methods`, `GET /checkout/shipping-methods`, `POST /checkout`, `POST /checkout/redirect-url`, `GET /orders/{key}`

### GET /checkout/config
- [ ] Returns the active checkout mode so the storefront knows which flow to render
  ```json
  { "mode": "redirect" }
  ```
  or
  ```json
  { "mode": "headless" }
  ```
- [ ] StoreFuse Next.js reads this on the cart page and shows either a "Proceed to Checkout" redirect button or the full headless checkout form

### Option A: Redirect mode (`mode: redirect`)
- [ ] `POST /checkout/redirect-url` - takes the current cart and returns the WooCommerce checkout URL
  - Builds the URL server-side so it is reliable across WooCommerce versions
  - Returns `{ "redirect_url": "https://yourstore.com/checkout/?..." }`
  - WooCommerce handles payment, order confirmation, and emails
  - Storefront simply redirects the user to this URL
- [ ] This is the recommended default - works with every payment gateway with zero extra config

### Option B: Headless mode (`mode: headless`)
- [ ] `GET /checkout/payment-methods` - list available payment gateways
  - Returns: id, title, description, icon URL, supported features
  - Only returns enabled gateways
- [ ] `GET /checkout/shipping-methods` - calculate shipping for a given address
  - Query params: `country`, `state`, `postcode`, `city`
  - Returns available shipping rates and their costs
- [ ] `POST /checkout` - submit order
  - Body: billing address, shipping address, payment method, payment data
  - Internally uses `WC()->checkout->process_checkout()` flow
  - Returns: order ID, order key, order status, payment redirect URL (for 3DS or gateway redirect)
  - Each payment gateway needs its own handler for payment data - this is the complex part
- [ ] `GET /orders/{key}` - order confirmation
  - Public endpoint, authenticated by order key (not user login)
  - Returns: order status, items, totals, billing/shipping address
  - Used for the branded "Thank You" page on the storefront
- [ ] Extension filter: `storefuse_bridge_checkout_response`

**Deliverable**: Store owner selects checkout mode once in WP admin. The Next.js storefront automatically uses the correct flow. Switching from redirect to headless (or back) requires no storefront code deployment.

---

## Phase 10: Content Module

**Goal**: Product reviews, blog posts, and pages - all the content that lives in WordPress but needs to appear on the headless storefront.

**Endpoints**: `GET /reviews`, `POST /reviews`, `GET /posts`, `GET /posts/{slug}`

- [ ] `GET /reviews?product_id=` - product reviews
  - Returns approved reviews only
  - Includes: author name, rating (1-5), comment, date, verified badge
  - Cached: 30-minute transient per product
- [ ] `POST /reviews` - submit a review
  - Body: product_id, rating, comment, author name, author email
  - Validates: product must exist, rating 1-5, honeypot anti-spam
  - Returns: pending/approved status message
- [ ] `GET /posts` - blog posts (uses standard WP REST, but proxied for CORS + normalisation)
  - Returns: id, slug, title, excerpt, date, featured image URL, categories
- [ ] `GET /posts/{slug}` - single post with full content
- [ ] Extension filter: `storefuse_bridge_review_response`

**Deliverable**: Reviews pull from real WooCommerce data. Blog is powered by WordPress with clean normalised responses.

---

## Phase 11: Webhooks & ISR Support

**Goal**: When a store owner saves a product, updates settings, or changes nav - the Next.js storefront's cache is automatically invalidated. No manual cache flushes needed.

- [ ] Admin setting: **Storefront URL** - the URL of the Next.js app
- [ ] Admin setting: **Revalidation Secret** - a shared secret for the webhook
- [ ] WordPress hook: `woocommerce_update_product` - send revalidation webhook
  - Payload: `{ type: "product", slug: "..." }`
- [ ] WordPress hook: `woocommerce_update_product_cat` - revalidate category
- [ ] WordPress hook: `storefuse_bridge_settings_updated` - revalidate all (full rebuild)
- [ ] WordPress hook: `wp_update_nav_menu` - revalidate navigation
- [ ] Webhook endpoint on StoreFuse side: `POST /api/revalidate`
  - Verifies secret, calls `revalidatePath()` for affected routes
- [ ] Delivery log in WP admin - shows recent webhook calls and their status codes

**Deliverable**: Product and content changes on WordPress are reflected on the storefront within seconds, automatically.

---

## Phase 12: Extensions & Third-Party Support

**Goal**: The plugin becomes a platform. Other plugins can extend it cleanly via WordPress hooks.

- [ ] **Yoast SEO** integration - if active, add `seo` object to product/page responses
  - `meta_title`, `meta_description`, `og_image`, `canonical_url`, `schema_json`
- [ ] **WPML / Polylang** integration - if active, accept `?lang=` param on all endpoints, return translated content
- [ ] **ACF (Advanced Custom Fields)** integration - if active, include ACF field groups on product/category responses under `custom_fields`
- [ ] **WooCommerce Subscriptions** - if active, expose subscription product data
- [ ] **WooCommerce Bundles / Composite Products** - if active, expose bundle structure
- [ ] Developer filter hooks documentation - every endpoint has a corresponding `storefuse_bridge_{endpoint}_response` filter documented

**Deliverable**: StoreFuse Bridge works with the most common WordPress/WooCommerce ecosystem plugins out of the box.

---

## Release Plan

| Version | Phases | Status | Description |
|---|---|---|---|
| v0.1.0 | 1 | Planned | Plugin shell, status endpoint, module system, auth/session/permissions/format/errors classes |
| v0.2.0 | 2 | Planned | Settings + navigation endpoints |
| v0.3.0 | 3 | Planned | Admin settings pages |
| v0.4.0 | 4 | Planned | Products + categories module |
| v0.5.0 | 5 | Planned | Search module |
| v0.6.0 | 6 | Planned | Cart module (session auth, guest cart) |
| v0.7.0 | 7 | Planned | Auth module (login, register, forgot/reset password, guest cart merge) |
| v0.8.0 | 8 | Planned | Customer APIs (orders, addresses, account, wishlist, downloads) |
| v0.9.0 | 9 | Planned | Checkout module (redirect + headless modes) |
| v0.10.0 | 10 | Planned | Content module (reviews, blog) |
| v0.11.0 | 11 | Planned | Webhooks + ISR revalidation |
| v1.0.0 | 12 | Planned | Extensions (Yoast, WPML, ACF) + production-ready |

---

## What Makes This Future-Proof

1. **Modules can be added without touching existing code** - new ecommerce features (subscriptions, bundles, loyalty points) are new modules, new endpoints, new files.

2. **Filters on every response** - third-party plugins can extend any response. If WooCommerce adds new product data in a new version, it can be piped through without modifying the plugin.

3. **API versioning** - if WooCommerce makes a breaking change (as it has with High-Performance Order Storage, Cart Blocks, etc.), a new `storefuse/v2` can be introduced alongside v1. Storefronts upgrade on their own schedule.

4. **WooCommerce version detection** - plugin checks which WC APIs are available at runtime. If `wc/store/v1` is not available (older WC), it falls back to `wc/v3`. If HPOS is enabled, it uses the correct order storage layer.

5. **Normalisation layer** - because WooCommerce internals are hidden behind the plugin's normalisation functions, WooCommerce can change storage format, field names, or DB schema without any impact on the StoreFuse frontend.

6. **Schema identifiers on every response** - when the API evolves, the `schema` field tells the frontend exactly which version of a response shape it received, making forward-compatibility detection built in from day one.

7. **StoreFuse type contracts** - every API resource has a named, stable shape (`Product`, `Order`, `Customer`, `Cart`, `Address`, `Review`, `Post`). These contracts live in `@storefuse/types`. The plugin returns them. The Next.js frontend consumes them. Any future client (React Native app, Flutter app, Vue storefront) consumes the same contracts. WooCommerce data maps into StoreFuse types inside the plugin - clients never see WooCommerce shapes. This is the foundation for an SDK, TypeScript generation, and eventual mobile app support.

---

## @storefuse/types - The Contract Layer

This is the most important long-term architecture decision for the entire platform.

Before building any endpoint, define the StoreFuse type contract for that resource. The contract is the source of truth. WooCommerce data maps into it. Every consumer (web, mobile, third-party) uses the same shape.

### Core contracts

```
Product       - id, slug, name, price{raw,formatted}, images[], stock, seo, categories[], variants[]
Category      - id, slug, name, description, image, count, parent
Cart          - items[], totals{subtotal,shipping,tax,discount,total}, coupons[], shipping_methods[]
Order         - id, number, status, date, items[], totals, payment{method,status}, tracking, addresses
Customer      - id, email, first_name, last_name, avatar_url, date_registered
Address       - first_name, last_name, company, address_1, address_2, city, state, postcode, country, phone
Review        - id, product_id, author, rating, comment, date, verified
Post          - id, slug, title, excerpt, content, date, image, categories[]
SearchResult  - id, slug, name, price{formatted}, image, in_stock, categories[]
```

### Location in the monorepo

```
packages/
  types/              (new package: @storefuse/types)
    src/
      product.ts
      category.ts
      cart.ts
      order.ts
      customer.ts
      address.ts
      review.ts
      post.ts
      search.ts
      index.ts
```

### Why this matters

- The bridge plugin PHP code normalises WooCommerce data into these shapes
- The Next.js frontend imports and uses these TypeScript types
- A React Native app uses the same types
- When WooCommerce changes an internal data field, only the normalisation function inside the plugin changes - no frontend, no app, no other consumer changes
- TypeScript types can be auto-generated as a client SDK in the future
- The `schema` field in every API response (`"storefuse.product.v1"`) maps directly to a contract name

### Migration strategy for existing frontend code

The StoreFuse frontend currently uses WooCommerce APIs directly for products and categories. The correct migration path:

1. Build the Bridge endpoint (`/storefuse/v1/products`)
2. Define the `Product` type contract
3. Build the normalisation function in PHP
4. Switch the Next.js adapter to call the Bridge endpoint - the TypeScript type is already defined so the switch is a one-line URL change in `lib/adapter.ts`
5. WooCommerce API calls disappear from the frontend one by one

Do not switch everything at once. Switch one resource type per release.

---

## The Layout API (Future Direction)

The `/homepage` endpoint starts with a flat object:

```json
{
  "hero": { "headline": "...", "cta": "..." },
  "featured_categories": [...]
}
```

This can evolve into a **block array** format - the storefront renders what the block array defines, not what is hardcoded:

```json
[
  { "type": "hero", "props": { "headline": "...", "cta": "..." } },
  { "type": "featuredProducts", "props": { "tag": "new-arrivals", "columns": 4 } },
  { "type": "trustBadges", "props": {} },
  { "type": "blogPosts", "props": { "limit": 3 } }
]
```

Why this matters:

- The WordPress admin defines page layout, not a developer
- Different homepage layouts per campaign, season, or A/B test - no code deploys
- The same block API can power category pages, landing pages, and custom pages
- This is how Shopify's Section/Block system works, and how Contentful content models work

This is the natural evolution of `/homepage` -> `/pages/{slug}` -> full visual page builder API. The architecture already supports it because the response passes through a filter (`storefuse_bridge_homepage_response`) that can return a block array instead of a flat object.

---

## Cache Invalidation Map

This is critical at scale. Every WordPress/WooCommerce event that changes data must invalidate the right cache groups:

| WordPress / WooCommerce Event | Cache Groups Invalidated |
|---|---|
| `woocommerce_update_product` | `products`, `search`, `homepage` (if product is featured) |
| `save_post_product` | `products`, `search` |
| `woocommerce_update_product_cat` | `categories`, `products` (category filter caches), `navigation` |
| `edited_product_cat` | `categories`, `navigation` |
| `storefuse_bridge_settings_updated` | `settings`, `homepage` |
| `wp_update_nav_menu` | `settings`, `navigation` |
| `wp_delete_nav_menu` | `settings`, `navigation` |
| `comment_post` | `reviews` (for the specific product) |
| `edit_comment` | `reviews` (for the specific product) |
| `transition_post_status` (publish) | `posts` |

The implementation uses `StoreFuse_Bridge_Cache::flush_group($group)` - a single call flushes all keys in that group. Group membership is tracked via a version key in the transient store (the "transient group versioning" pattern - no full DB scan needed).

---

## Priority Build Order

Based on the architecture analysis, this is the correct order - each phase unblocks the next:

| # | Endpoint(s) | Why First |
|---|---|---|
| 1 | `/status` | Confirms plugin foundation works |
| 2 | `/settings` | Most impactful endpoint - replaces all hardcoded storefront config in one request |
| 3 | `/products` | Core commerce - nothing else renders without products |
| 4 | `/categories` | Needed for navigation, category pages, product breadcrumbs |
| 5 | `/search` | Relatively simple once products exist |
| 6 | `/cart` | Hard - session management, nonce handling, WC internals |
| 7 | `/checkout` | Hardest - payment gateway integration per gateway |

Do not start cart until products is solid. Do not start checkout until cart is stable.

---

## Design Boundaries

These are explicit decisions about what this plugin will NOT become. They are documented here so future contributors have context for why certain feature requests should be declined.

**No generic CMS features**

StoreFuse Bridge is a commerce API. Posts and reviews exist in scope because stores need content and social proof to sell products - not because StoreFuse Bridge is a general-purpose headless CMS. StoreFuse Bridge will not become: a news site API, a portfolio API, a generic custom post type browser, or a Gutenberg-replacement data layer. Every feature request passes this test: does a store customer need this to discover, evaluate, or purchase a product?

**No visual page builder**

The Layout API block array (see future direction above) is the correct long-term path. But it is a data API, not a visual builder. StoreFuse Bridge does not become Elementor, Gutenberg, or a Shopify Sections replacement. The storefront renders blocks that WordPress defines. The visual editor, if one ever exists, lives in the admin settings pages - not in a Gutenberg-replacement canvas inside WordPress.

**No GraphQL**

REST is the correct choice for this system. The entire advantage of StoreFuse Bridge is opinionated, cacheable, storefront-optimised responses. GraphQL removes: HTTP-level caching (because everything is a POST), response shape guarantees (clients request arbitrary fields), and the ability to pre-aggregate data server-side. Every argument for GraphQL in this context is solved better by REST + well-designed endpoints. Do not add GraphQL support.

**No plugin marketplace before core stability**

The plugin needs: one stable storefront, one stable API, one stable checkout flow. That is the foundation. A plugin marketplace, paid extensions, or an "addon ecosystem" before v1.0 is stable would fragment developer attention and ship an unreliable platform. Extensions are documented (via WordPress filters), but no storefront for selling them should exist until the core is production-tested.

**No direct database queries**

All data access goes through WC functions and WordPress APIs. Never bypass them with `$wpdb` queries. WooCommerce has changed its storage layer (HPOS), and WordPress has changed post storage. The compatibility layer exists for this reason.

**No jQuery dependency**

Admin JS is vanilla JavaScript. No jQuery. This avoids loading jQuery on every admin page just for the plugin's settings page, and keeps the codebase maintainable as WordPress eventually deprecates its jQuery version.

---

## Branding Note

Internally and for the current repo, **StoreFuse Bridge** is the correct name. It communicates purpose clearly.

As the plugin matures and becomes the full commerce API engine, a rename to something like **StoreFuse Gateway**, **StoreFuse API**, or **StoreFuse Commerce** may be appropriate. The plugin code already uses the prefix `storefuse_bridge_` - a rename is a search-and-replace when the time comes. This is not a priority now.

5. **No direct database queries** - all WooCommerce data goes through WC functions. WooCommerce can change its storage layer (and it has) without breaking the plugin.

6. **Semantic versioning + changelog** - every release documents breaking changes. Storefronts can pin to a major version.


---

## Phase 1: Plugin Foundation

**Goal**: A working plugin that activates cleanly and registers the API namespace.

### Tasks

- [ ] Create plugin entry file `storefuse-bridge.php`
  - [ ] Plugin header (name, version, author, requires WP/WC)
  - [ ] Activation hook - check WooCommerce is active
  - [ ] Deactivation hook - cleanup
  - [ ] Autoloader setup
- [ ] Register REST API namespace `storefuse/v1`
- [ ] Add `GET /storefuse/v1/status` health check endpoint
  - Returns: plugin version, WP version, WC version, site URL
- [ ] Basic folder structure
  ```
  storefuse-bridge/
    storefuse-bridge.php       - Main plugin file
    includes/
      class-rest-api.php       - REST route registration
      class-settings.php       - Settings reader
      class-admin.php          - Admin page
    admin/
      views/
        settings-page.php      - Admin UI template
    assets/
      admin.css
      admin.js
  ```
- [ ] Confirm plugin activates on local WordPress (XAMPP)

**Deliverable**: Plugin activates, `/storefuse/v1/status` returns 200.

---

## Phase 2: Core Settings Endpoint

**Goal**: Expose all site identity and WooCommerce store settings that are currently hardcoded in the StoreFuse storefront.

### Endpoint: `GET /storefuse/v1/settings`

#### Site Identity (from WordPress core)
- [ ] Site name - `get_bloginfo('name')`
- [ ] Tagline - `get_bloginfo('description')`
- [ ] Site URL - `get_bloginfo('url')`
- [ ] Site logo URL - resolve attachment ID from `get_theme_mod('custom_logo')` to full URL
- [ ] Favicon URL - `get_site_icon_url()`
- [ ] Admin email - `get_bloginfo('admin_email')`

#### WooCommerce Store Settings (from WC options)
- [ ] Currency code - `get_woocommerce_currency()`
- [ ] Currency symbol - `get_woocommerce_currency_symbol()`
- [ ] Currency position - `get_option('woocommerce_currency_pos')`
- [ ] Price decimal separator
- [ ] Price thousand separator
- [ ] Number of decimals

#### Shipping Config
- [ ] Free shipping minimum order amount - read from active WC free shipping methods
- [ ] COD enabled - check if COD gateway is active

#### Store Policies (custom, from plugin options)
- [ ] Return policy days (configurable in plugin admin)
- [ ] Free shipping threshold label text

**Deliverable**: Storefront can replace hardcoded logo, favicon, site name, currency from this endpoint.

---

## Phase 3: Navigation Endpoint

**Goal**: Make header nav links and category megamenu fully dynamic from WordPress.

### Endpoint: `GET /storefuse/v1/navigation`

- [ ] Read registered WordPress nav menus assigned to locations
  - `primary` / `main` - header navigation
  - `footer` - footer links
- [ ] Resolve each menu item:
  - Label, URL, target, parent ID (for nested menus)
  - If item is a product category - include `slug` and `image`
- [ ] Fallback: if no menus are assigned, return sensible defaults
  ```json
  {
    "header": [
      { "label": "Shop", "href": "/shop" },
      { "label": "Cart", "href": "/cart" }
    ],
    "footer": []
  }
  ```
- [ ] Return category megamenu items separately (top-level WC categories)

**Deliverable**: Header `NAV_LINKS` and `CATEGORIES` arrays are no longer hardcoded.

---

## Phase 4: Admin Settings Page

**Goal**: Let store owners configure the things that don't exist as native WordPress settings.

### Settings to configure in admin UI

- [ ] Announcement bar
  - Text (e.g. "Free shipping on orders over 999")
  - Enabled/disabled toggle
  - Background color
- [ ] Trust badges (up to 4)
  - Icon (emoji or upload)
  - Title
  - Description
- [ ] Social media links
  - Instagram, Facebook, Twitter/X, YouTube, Pinterest, WhatsApp
- [ ] Homepage hero
  - Headline text
  - Subheadline text
  - Primary CTA label + URL
  - Secondary CTA label + URL
  - Hero image (media upload)
- [ ] Store policies text
  - Return policy days
  - Free shipping threshold amount

### Admin Page Structure
- Located under **WooCommerce > StoreFuse** in WP admin menu
- Saved using `update_option('storefuse_bridge_settings', ...)`
- All settings validated and sanitized on save
- Simple tabbed interface: General | Navigation | Homepage | Social

**Deliverable**: Store owner can configure all storefront content from WordPress admin - no code changes needed.

---

## Phase 5: Homepage Configuration Endpoint

**Goal**: Hero section, featured categories, and announcement bar are all live from WordPress.

### Endpoint: `GET /storefuse/v1/homepage`

- [ ] Announcement bar (from Phase 4 admin settings)
- [ ] Hero section
  - Headline, subheadline, CTAs, background image
- [ ] Featured categories (admin-curated list, subset of WC categories)
  - Each item: label, slug, icon/image, link
- [ ] Trust badges row
- [ ] Banner/promotional section (optional, for sale campaigns)

**Deliverable**: The StoreFuse home page (`app/page.tsx`) can be fully data-driven. No hardcoded arrays.

---

## Phase 6: StoreFuse Integration

**Goal**: Wire the plugin API into the StoreFuse `@storefuse/core` adapter so any storefront consumes it automatically.

### StoreFuse-side changes (in `storefuse` repo, not this plugin)

- [ ] Add `bridge.url` to `StoreFuseConfig` type
- [ ] Create `fetchBridgeSettings()` in adapter or a new `module-bridge`
- [ ] Create `SiteSettingsProvider` React context
  - Wraps `StoreFuseShell`
  - Fetches `/storefuse/v1/settings` on server render (Next.js RSC)
  - Makes data available via `useSiteSettings()` hook
- [ ] Update `Header` in `theme-core` to use `useSiteSettings()` for nav links, logo, announcement bar
- [ ] Update `Footer` in `theme-core` to use `useSiteSettings()` for social links, trust badges
- [ ] Update `app/page.tsx` to use `/storefuse/v1/homepage` for hero and categories

**Deliverable**: A storefront connected to StoreFuse Bridge shows real logo, real nav, real hero - zero hardcoded content.

---

## Phase 7: WooCommerce Checkout Fix

**Goal**: Make cart-to-checkout redirect reliable for real users.

### Problem
The current `module-checkout-redirect` builds a URL with `add-to-cart[]` query params. This is unreliable for multiple items across WooCommerce versions.

### Solution: WC Store API
- [ ] Use `wc/store/v1/cart` to create a server-side cart session
  - `POST wc/store/v1/cart/add-item` for each cart item
- [ ] Read the WC Nonce from response headers (`X-WC-Store-API-Nonce`)
- [ ] Redirect user to `/checkout/` - WooCommerce reads the session cookie
- [ ] OR: Use WC Store API full headless checkout
  - `POST wc/store/v1/checkout` with billing, shipping, payment method
  - Returns order ID and confirmation URL

### Decision Point (to discuss before building)
- Option A (Redirect): Simpler, WC handles payment UI
- Option B (Headless): Full Next.js checkout, requires per-gateway work

**Deliverable**: Checkout works reliably for real purchases. Orders appear in WooCommerce admin.

---

## Release Plan

| Version | Phases | Description |
|---|---|---|
| v0.1.0 | 1 | Plugin scaffolding + status endpoint |
| v0.2.0 | 2 | Core settings (logo, favicon, currency) |
| v0.3.0 | 3 | Navigation endpoint |
| v0.4.0 | 4 | Admin settings page |
| v0.5.0 | 5 | Homepage configuration endpoint |
| v0.6.0 | 1-6 | StoreFuse integration wired end-to-end |
| v1.0.0 | 7 | Checkout fix - production-ready for real stores |

---

## Out of Scope (v1.0)

These are valid future features but not needed for v1:

- WooCommerce Subscriptions support
- Multisite / network activation
- REST API authentication (all endpoints are public read-only)
- Webhooks / push notifications to the storefront
- A/B testing banners
- Blog/posts API (WordPress REST already covers this natively)
