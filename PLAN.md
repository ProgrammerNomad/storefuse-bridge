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

---

## Full API Surface (Target)

```
GET  /storefuse/v1/status

GET  /storefuse/v1/settings
GET  /storefuse/v1/navigation
GET  /storefuse/v1/homepage

GET  /storefuse/v1/products
GET  /storefuse/v1/products/{slug}
GET  /storefuse/v1/categories
GET  /storefuse/v1/categories/{slug}
GET  /storefuse/v1/search?q=

GET  /storefuse/v1/cart
POST /storefuse/v1/cart/add
PUT  /storefuse/v1/cart/update
DELETE /storefuse/v1/cart/remove
POST /storefuse/v1/cart/coupon
DELETE /storefuse/v1/cart/coupon

GET  /storefuse/v1/checkout/config
GET  /storefuse/v1/checkout/payment-methods
GET  /storefuse/v1/checkout/shipping-methods
POST /storefuse/v1/checkout
POST /storefuse/v1/checkout/redirect-url
GET  /storefuse/v1/orders/{key}

GET  /storefuse/v1/reviews?product_id=
POST /storefuse/v1/reviews
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
- [ ] Register `GET /storefuse/v1/status` endpoint
  - Returns: plugin version, WP version, WC version, PHP version, active modules list
- [ ] Confirm plugin activates on XAMPP local WordPress

**Deliverable**: Plugin activates. `/storefuse/v1/status` returns 200 with version info.

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
  - Return `cart_token` in response headers for stateless clients
- [ ] `GET /cart` - return current cart
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

## Phase 7: Checkout Module

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

## Phase 8: Content Module

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

## Phase 9: Webhooks & ISR Support

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

## Phase 10: Extensions & Third-Party Support

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
| v0.1.0 | 1 | Planned | Plugin shell, status endpoint, module system |
| v0.2.0 | 2 | Planned | Settings + navigation endpoints |
| v0.3.0 | 3 | Planned | Admin settings page |
| v0.4.0 | 4 | Planned | Products + categories module |
| v0.5.0 | 5 | Planned | Search module |
| v0.6.0 | 6 | Planned | Cart module |
| v0.7.0 | 7 | Planned | Checkout module |
| v0.8.0 | 8 | Planned | Content module (reviews, blog) |
| v0.9.0 | 9 | Planned | Webhooks + ISR revalidation |
| v1.0.0 | 10 | Planned | Extensions (Yoast, WPML, ACF) + production-ready |

---

## What Makes This Future-Proof

1. **Modules can be added without touching existing code** - new ecommerce features (subscriptions, bundles, loyalty points) are new modules, new endpoints, new files.

2. **Filters on every response** - third-party plugins can extend any response. If WooCommerce adds new product data in a new version, it can be piped through without modifying the plugin.

3. **API versioning** - if WooCommerce makes a breaking change (as it has with High-Performance Order Storage, Cart Blocks, etc.), a new `storefuse/v2` can be introduced alongside v1. Storefronts upgrade on their own schedule.

4. **WooCommerce version detection** - plugin checks which WC APIs are available at runtime. If `wc/store/v1` is not available (older WC), it falls back to `wc/v3`. If HPOS is enabled, it uses the correct order storage layer.

5. **Normalisation layer** - because WooCommerce internals are hidden behind the plugin's normalisation functions, WooCommerce can change storage format, field names, or DB schema without any impact on the StoreFuse frontend.

6. **Schema identifiers on every response** - when the API evolves, the `schema` field tells the frontend exactly which version of a response shape it received, making forward-compatibility detection built in from day one.

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
