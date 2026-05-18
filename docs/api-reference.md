# API Reference - StoreFuse Bridge

**Base URL**: `https://your-wordpress-site.com/wp-json/storefuse/v1`

**Authentication**: Public endpoints require no auth. Customer endpoints require a logged-in session (WordPress auth cookie set by `POST /auth/login`). Cart/checkout write endpoints require an `X-WP-Nonce` header. Auth write endpoints require an `X-WP-Nonce` header (CSRF protection).

All responses: `Content-Type: application/json`.

**Standard response envelope** - every response wraps data in:
```json
{
  "schema": "storefuse.{resource}.v1",
  "api_version": "1.0.0",
  "data": {}
}
```

Why: the `schema` field lets the frontend detect which version of a response shape it received. When `storefuse/v2` is released, `schema` becomes `storefuse.product.v2` and the frontend knows without checking headers.

**Standard response headers:**

Public endpoints (`/products`, `/categories`, `/homepage`, `/search`, etc.):
```
X-StoreFuse-Bridge-Version: 1.0.0
X-StoreFuse-Cache: HIT | MISS
Cache-Control: public, max-age=600, s-maxage=600
```

Session endpoints (`/cart`, `/account`, `/orders`, `/auth/*`):
```
X-StoreFuse-Bridge-Version: 1.0.0
Cache-Control: no-store
X-StoreFuse-Cart-Token: {session_id}
```

These headers are set by the infrastructure, not by individual modules. Never mix them - session data cached publicly causes cart leaks and auth failures.

**Canonical data shapes** - two shapes used everywhere in all responses:

Price:
```json
{ "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." }
```

Image:
```json
{ "url": "https://...", "alt": "Product name", "width": 1200, "height": 1200, "srcset": ["url @300w", "url @600w", "url @1200w"] }
```

No endpoint returns a price as a plain string. No endpoint returns an image as a plain URL. These are contracts.

**Error response shape** - every error uses this exact structure:
```json
{
  "schema": "storefuse.error.v1",
  "api_version": "1.0.0",
  "error": {
    "code": "product_not_found",
    "message": "Product not found.",
    "status": 404
  }
}
```

The `status` field is included in the body (not only as the HTTP status code) so clients that don't inspect status codes still get the correct error context. All error codes are snake_case strings.

---

## API Index

```
# Health & Config
GET  /status
GET  /settings
GET  /navigation
GET  /homepage

# Catalogue (public, cached)
GET  /products
GET  /products/{slug}
POST /products/{slug}/notify        back-in-stock alert signup
GET  /categories
GET  /categories/{slug}
GET  /attributes                    attribute list + terms for filter sidebar
GET  /tags
GET  /search?q=

# Content (public)
GET  /reviews?product_id=
POST /reviews
GET  /posts
GET  /posts/{slug}

# Utilities (public)
GET  /utils/countries
GET  /utils/pincode/{pincode}       India: pincode -> city/state

# Authentication
POST /auth/register
POST /auth/login
POST /auth/logout
GET  /auth/me
POST /auth/forgot-password
POST /auth/reset-password

# Customer (requires login)
GET  /account
PUT  /account
POST /account/change-password
GET  /orders
GET  /orders/{id}
POST /orders/{id}/cancel
POST /orders/{id}/reorder
POST /orders/{id}/return-request
GET  /orders/{id}/tracking
GET  /orders/{id}/invoice
GET  /addresses
PUT  /addresses/billing
PUT  /addresses/shipping
GET  /wishlist
POST /wishlist/add
DELETE /wishlist/remove
GET  /downloads

# Cart (session, nonce required for writes)
GET    /cart
POST   /cart/add
PUT    /cart/update
DELETE /cart/remove
POST   /cart/coupon
DELETE /cart/coupon

# Checkout
GET  /checkout/config
GET  /checkout/payment-methods
GET  /checkout/shipping-methods
POST /checkout
POST /checkout/redirect-url
GET  /orders/{key}                  public, by order key - Thank You page
```

---

## Status

### GET /status

Health check. Confirms plugin is active, lists available modules, and exposes a `features` capability map.

```json
{
  "schema": "storefuse.status.v1",
  "api_version": "1.0.0",
  "data": {
    "status": "ok",
    "plugin": "StoreFuse Bridge",
    "version": "0.4.0",
    "wordpress": "6.5.3",
    "woocommerce": "9.0.0",
    "php": "8.2.0",
    "site_url": "https://yourstore.com",
    "api_namespace": "storefuse/v1",
    "modules": {
      "settings": true,
      "products": true,
      "categories": true,
      "search": true,
      "cart": true,
      "checkout": true,
      "content": true,
      "webhooks": false
    },
    "features": {
      "hpos": true,
      "store_api": true,
      "headless_checkout": false,
      "subscriptions": false,
      "wpml": false,
      "polylang": false,
      "yoast_seo": true,
      "rank_math": false,
      "acf": false
    }
  }
}
```

The `features` map is detected at request time via `class_exists()` and `is_plugin_active()`. The storefront reads this once on startup to determine runtime behaviour (e.g. whether to show a language switcher, which SEO data fields to expect). No hardcoded capability checks in the storefront codebase.

---

## Settings Module

### GET /settings

The primary startup endpoint. One request - all site identity, store config, navigation, and social data.  
**Cache**: 1 hour. Invalidated when admin saves settings or updates nav menus.

```json
{
  "schema": "storefuse.settings.v1",
  "api_version": "1.0.0",
  "data": {
    "site": {
      "name": "My Festive Store",
      "tagline": "Handcrafted for modern homes",
      "url": "https://yourstore.com",
      "logo_url": "https://yourstore.com/wp-content/uploads/logo.png",
      "favicon_url": "https://yourstore.com/wp-content/uploads/favicon.ico",
      "admin_email": "admin@yourstore.com"
    },
    "store": {
      "currency": "INR",
      "currency_symbol": "₹",
      "currency_position": "left",
      "price_decimal_separator": ".",
      "price_thousand_separator": ",",
      "price_decimals": 0,
      "free_shipping_threshold": 999,
      "free_shipping_label": "Free shipping on orders over ₹999",
      "return_policy_days": 7,
      "cod_enabled": true
    },
    "header": {
      "announcement_bar_enabled": true,
      "announcement_bar_text": "🚚 Free shipping on orders over ₹999 · Easy 7-day returns",
      "announcement_bar_bg_color": "#E85D04"
    },
    "trust_badges": [
      { "icon": "🚚", "title": "Free Shipping", "description": "On orders over ₹999" },
      { "icon": "↩", "title": "Easy Returns", "description": "7-day hassle-free returns" },
      { "icon": "🔒", "title": "Secure Payment", "description": "100% protected checkout" },
      { "icon": "🤝", "title": "Handmade Quality", "description": "Authentic artisan products" }
    ],
    "social_links": {
      "instagram": "https://instagram.com/yourstore",
      "facebook": "https://facebook.com/yourstore",
      "twitter": null,
      "youtube": null,
      "pinterest": null,
      "whatsapp": null
    },
    "navigation": {
      "main": [
        { "id": 1, "label": "Home", "href": "/", "target": "_self", "parent": null },
        { "id": 2, "label": "Shop", "href": "/shop", "target": "_self", "parent": null }
      ],
      "categories": [
        {
          "id": 12, "label": "Festive Decor", "slug": "festive-decor",
          "href": "/category/festive-decor", "icon": "🪔",
          "image_url": "https://yourstore.com/wp-content/uploads/cat.jpg"
        }
      ],
      "footer": [
        { "id": 10, "label": "Shop", "href": "/shop", "target": "_self", "parent": null }
      ]
    }
  }
}
```

### GET /navigation

Navigation menus only. Use to refresh nav without re-fetching all settings.  
Same shape as `settings.navigation`. **Cache**: 1 hour.

### GET /homepage

Homepage configuration: hero, featured categories, announcement bar.  
**Cache**: 15 minutes. Extension filter `storefuse_bridge_homepage_response`.

**Current format (v1) - flat config object:**

```json
{
  "schema": "storefuse.homepage.v1",
  "api_version": "1.0.0",
  "data": {
    "announcement_bar": {
      "enabled": true,
      "text": "🪔 New Festive Collection 2026",
      "bg_color": "#E85D04",
      "link": "/shop?tag=new-2026"
    },
    "hero": {
      "badge_text": "🪔 New Festive Collection 2026",
      "headline": "Handcrafted decor for modern homes",
      "headline_highlight": "modern homes",
      "subheadline": "Discover artisan-made festive pieces, diyas, jewelry and home accents.",
      "cta_primary_label": "Shop Now",
      "cta_primary_href": "/shop",
      "cta_secondary_label": "New Arrivals",
      "cta_secondary_href": "/shop?sort=newest",
      "image": { "url": "https://yourstore.com/wp-content/uploads/hero.jpg", "alt": "Festive Collection 2026", "width": 1920, "height": 800, "srcset": [] },
      "rating_text": "4.8/5 from 2,400+ reviews",
      "shipping_text": "Free shipping over ₹999"
    },
    "featured_categories": [
      {
        "label": "Festive Decor", "slug": "festive-decor",
        "href": "/category/festive-decor", "icon": "🪔",
        "color_class": "bg-orange-50 hover:bg-orange-100"
      }
    ],
    "trust_items": [
      { "icon": "🤝", "title": "Handmade Quality", "description": "Every piece crafted by skilled artisans" }
    ]
  }
}
```

**Future direction - block array format (v2 milestone):**

The `storefuse_bridge_homepage_response` filter can return a block array today. The full Layout API - where WordPress defines page structure and the storefront renders whatever blocks are returned - is a v2 milestone.

```json
[
  { "type": "hero", "props": { "headline": "...", "cta_label": "Shop Now", "cta_href": "/shop" } },
  { "type": "featuredProducts", "props": { "tag": "new-arrivals", "columns": 4 } },
  { "type": "trustBadges", "props": {} },
  { "type": "blogPosts", "props": { "limit": 3 } }
]
```

---

## Products Module

### GET /products

Product list. Storefront-optimised with pagination.  
**Cache**: 10 minutes per query hash.

**Query params:**

| Param | Type | Default | Description |
|---|---|---|---|
| `per_page` | int | 12 | Items per page (max 100) |
| `page` | int | 1 | Page number |
| `category` | string | - | Category slug |
| `tag` | string | - | Tag slug |
| `orderby` | string | `date` | `date`, `price`, `popularity`, `rating`, `name` |
| `order` | string | `desc` | `asc` or `desc` |
| `on_sale` | bool | - | Only sale products |
| `featured` | bool | - | Only featured products |
| `min_price` | number | - | Minimum price filter |
| `max_price` | number | - | Maximum price filter |
| `search` | string | - | Text search |
| `attribute[pa_color]` | string | - | Filter by attribute term slug. Stack multiple: `attribute[pa_size]=large&attribute[pa_color]=red` |

**Response:**
```json
{
  "schema": "storefuse.products.v1",
  "api_version": "1.0.0",
  "data": {
    "products": [
      {
        "id": "123",
        "slug": "handcrafted-diya-set",
        "name": "Handcrafted Diya Set",
        "short_description": "Set of 6 hand-painted clay diyas.",
        "price":         { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
        "regular_price": { "raw": 699.00, "formatted": "Rs. 699.00", "currency": "INR", "symbol": "Rs." },
        "sale_price":    { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
        "on_sale": true,
        "sale_ends_at": null,
        "stock_status": "instock",
        "stock_quantity": 24,
        "sku": "DIYA-001",
        "images": [
          { "url": "https://yourstore.com/wp-content/uploads/diya.jpg", "alt": "Diya Set", "width": 1200, "height": 1200, "srcset": ["...@300w", "...@600w", "...@1200w"] }
        ],
        "categories": [
          { "id": "12", "name": "Festive Decor", "slug": "festive-decor" }
        ],
        "tags": [
          { "id": "5", "name": "Diwali", "slug": "diwali" }
        ],
        "attributes": [],
        "average_rating": "4.80",
        "rating_count": 42
      }
    ],
    "pagination": {
      "total": 87,
      "total_pages": 8,
      "page": 1,
      "per_page": 12
    }
  }
}
```

Pagination note: `page` + `per_page` for v1. The `pagination` envelope is designed to be extended with `cursor` support without breaking existing consumers.

### GET /products/{slug}

Single product with related products and breadcrumb.  
**Cache**: 10 minutes per slug. One request renders the full product page.

The `seo` field is populated by the active SEO plugin (Yoast, RankMath, etc.) via the `storefuse_bridge_product_response` filter. If no SEO plugin is active, it falls back to the product name and short description. The frontend never sees raw meta key names like `_yoast_wpseo_title`.

```json
{
  "schema": "storefuse.product.v1",
  "api_version": "1.0.0",
  "data": {
    "product": {
      "id": "123",
      "slug": "handcrafted-diya-set",
      "name": "Handcrafted Diya Set",
      "description": "<p>Full HTML product description.</p>",
      "short_description": "Set of 6 hand-painted clay diyas.",
      "price":         { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
      "regular_price": { "raw": 699.00, "formatted": "Rs. 699.00", "currency": "INR", "symbol": "Rs." },
      "sale_price":    { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
      "on_sale": true,
      "sale_ends_at": "2026-05-20T23:59:59Z",
      "stock_status": "instock",
      "stock_quantity": 24,
      "sku": "DIYA-001",
      "images": [
        { "url": "https://yourstore.com/wp-content/uploads/diya.jpg", "alt": "Diya Set", "width": 1200, "height": 1200, "srcset": ["...@300w", "...@600w", "...@1200w"] }
      ],
      "categories": [
        { "id": "12", "name": "Festive Decor", "slug": "festive-decor" }
      ],
      "attributes": [
        { "name": "Size", "slug": "pa_size", "options": ["S", "M", "L", "XL"] }
      ],
      "variations": [
        {
          "id": "124",
          "sku": "DIYA-001-L",
          "attributes": { "pa_size": "L" },
          "price": { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
          "stock_status": "instock",
          "stock_quantity": 8
        }
      ],
      "average_rating": "4.80",
      "rating_count": 42,
      "seo": {
        "title": "Handcrafted Diya Set - My Festive Store",
        "description": "Buy authentic hand-painted clay diyas online. Set of 6. Free shipping over ₹999.",
        "og_image": "https://yourstore.com/wp-content/uploads/diya.jpg",
        "canonical_url": "https://yourstore.com/product/handcrafted-diya-set"
      }
    },
    "related_products": [ { "id": "124", "name": "...", "...all product fields": "..." } ],
    "breadcrumb": [
      { "label": "Home", "href": "/" },
      { "label": "Festive Decor", "href": "/category/festive-decor" },
      { "label": "Handcrafted Diya Set", "href": null }
    ]
  }
}
```

---

## Categories Module

### GET /categories

Full category tree with hierarchy.  
**Cache**: 1 hour.

```json
{
  "categories": [
    {
      "id": "12",
      "name": "Festive Decor",
      "slug": "festive-decor",
      "description": "Handcrafted festive decoration pieces.",
      "image": { "url": "https://yourstore.com/wp-content/uploads/festive.jpg", "alt": "Festive Decor", "width": 800, "height": 800, "srcset": [] },
      "product_count": 34,
      "parent": null,
      "children": [
        { "id": "18", "name": "Diyas", "slug": "diyas", "product_count": 12, "parent": "12", "children": [] }
      ]
    }
  ]
}
```

### GET /categories/{slug}

Single category with its first page of products.  
**Cache**: 10 minutes.

```json
{
  "category": { "...category object..." },
  "products": { "...same as GET /products response..." }
}
```

---

## Attributes Module

### GET /attributes

Returns all product attributes and their terms. Used to build the filter sidebar on shop/category pages.  
**Cache**: 1 hour.

```json
{
  "schema": "storefuse.attributes.v1",
  "api_version": "1.0.0",
  "data": [
    {
      "id": "3",
      "name": "Size",
      "slug": "pa_size",
      "terms": [
        { "id": "10", "name": "S", "slug": "s", "count": 24 },
        { "id": "11", "name": "M", "slug": "m", "count": 31 },
        { "id": "12", "name": "L", "slug": "l", "count": 28 },
        { "id": "13", "name": "XL", "slug": "xl", "count": 19 }
      ]
    },
    {
      "id": "4",
      "name": "Color",
      "slug": "pa_color",
      "terms": [
        { "id": "20", "name": "Red", "slug": "red", "count": 15 },
        { "id": "21", "name": "Blue", "slug": "blue", "count": 18 }
      ]
    }
  ]
}
```

Frontend usage: fetch once on app startup, store in state. Render as checkboxes/swatches. Pass selected terms as `attribute[pa_size]=l&attribute[pa_color]=red` on `GET /products`.

---

## Tags Module

### GET /tags

All product tags. Used for tag filter chips or tag cloud on shop page.  
**Cache**: 1 hour.

```json
{
  "schema": "storefuse.tags.v1",
  "api_version": "1.0.0",
  "data": [
    { "id": "5", "name": "Diwali", "slug": "diwali", "count": 34 },
    { "id": "6", "name": "New Arrival", "slug": "new-arrival", "count": 12 },
    { "id": "7", "name": "Bestseller", "slug": "bestseller", "count": 56 }
  ]
}
```

---

## Back-in-Stock

### POST /products/{slug}/notify

Registers a back-in-stock alert for an out-of-stock product. Requires user email. Can be used by guests and logged-in users.

**Body**: `{ "email": "user@example.com" }`

**Response**: `{ "data": { "registered": true } }`

The actual notification email is handled by WooCommerce or a plugin (Back In Stock Notifier, etc.) via the `storefuse_bridge_notify_registered` action hook:

```php
do_action('storefuse_bridge_notify_registered', $product_id, $email);
```

If no plugin is listening, the bridge stores the email in a custom table and a background process checks stock on WC `woocommerce_product_set_stock_status` hook.

---

## Search Module

### GET /search

**Query params**: `q` (required, min 2 chars), `per_page` (default 10), `page`  
**Cache**: 5 minutes per query string.

```json
{
  "results": [
    {
      "id": 123,
      "name": "Handcrafted Diya Set",
      "slug": "handcrafted-diya-set",
      "price": 499,
      "regular_price": 699,
      "sale_price": 499,
      "image": "https://yourstore.com/wp-content/uploads/diya.jpg",
      "categories": ["Festive Decor"],
      "in_stock": true
    }
  ],
  "total": 5,
  "query": "diya"
}
```

---

## Cart Module

All cart endpoints require the `X-WC-Nonce` header (obtained from `GET /cart` response headers).

### GET /cart

Returns current cart. Issues a new cart nonce in the `X-WC-Nonce` response header.

```json
{
  "items": [
    {
      "key": "abc123",
      "product_id": "123",
      "variation_id": null,
      "name": "Handcrafted Diya Set",
      "quantity": 2,
      "image": { "url": "https://yourstore.com/...", "alt": "Handcrafted Diya Set", "width": 600, "height": 600, "srcset": [] },
      "price":          { "raw": 499.00, "formatted": "Rs. 499.00", "currency": "INR", "symbol": "Rs." },
      "line_subtotal":  { "raw": 998.00, "formatted": "Rs. 998.00", "currency": "INR", "symbol": "Rs." }
    }
  ],
  "totals": {
    "subtotal":  { "raw": 998.00, "formatted": "Rs. 998.00",  "currency": "INR", "symbol": "Rs." },
    "discount":  { "raw": 0,      "formatted": "Rs. 0.00",    "currency": "INR", "symbol": "Rs." },
    "shipping":  { "raw": 0,      "formatted": "Rs. 0.00",    "currency": "INR", "symbol": "Rs." },
    "tax":       { "raw": 0,      "formatted": "Rs. 0.00",    "currency": "INR", "symbol": "Rs." },
    "total":     { "raw": 998.00, "formatted": "Rs. 998.00",  "currency": "INR", "symbol": "Rs." }
  },
  "coupons": [],
  "item_count": 2,
  "shipping_methods": [
    { "id": "free_shipping", "label": "Free Shipping", "cost": { "raw": 0, "formatted": "Rs. 0.00", "currency": "INR", "symbol": "Rs." } }
  ],
  "needs_payment": true
}
```

### POST /cart/add

**Body**: `{ "product_id": 123, "quantity": 1, "variation_id": null }`  
**Returns**: Updated cart (same as GET /cart).  
**Errors**: `out_of_stock`, `cannot_be_purchased`, `invalid_product`.

### PUT /cart/update

**Body**: `{ "cart_item_key": "abc123", "quantity": 3 }`  
**Returns**: Updated cart.

### DELETE /cart/remove

**Body**: `{ "cart_item_key": "abc123" }`  
**Returns**: Updated cart.

### POST /cart/coupon

**Body**: `{ "coupon_code": "DIWALI20" }`  
**Returns**: Updated cart, or error `invalid_coupon` / `coupon_already_applied`.

### DELETE /cart/coupon

**Body**: `{ "coupon_code": "DIWALI20" }`  
**Returns**: Updated cart.

---

## Auth Module

Auth uses WordPress native cookies. No token is returned in the response body. The browser receives an HTTP-only cookie from `wp_set_auth_cookie()` and includes it automatically on every subsequent request.

**CSRF protection**: All write endpoints (`login`, `logout`, `register`, `forgot-password`, `reset-password`) require the `X-WP-Nonce` header.

### POST /auth/register

Creates a new customer account and immediately logs them in.

**Body**:
```json
{ "email": "user@example.com", "password": "secure123", "first_name": "Priya", "last_name": "Sharma" }
```

**Response**:
```json
{
  "schema": "storefuse.auth.v1",
  "api_version": "1.0.0",
  "data": {
    "user": {
      "id": 42,
      "name": "Priya Sharma",
      "email": "user@example.com",
      "avatar_url": "https://gravatar.com/..."
    }
  }
}
```

Auto-login after registration is mandatory. Do not make users log in after registering - it kills conversion rate.

### POST /auth/login

```json
{ "email": "user@example.com", "password": "secure123" }
```

**Response**: Same shape as `/auth/register` response. HTTP-only auth cookie is set by the server. Guest cart items are merged into the user cart automatically.

**Error responses**: `{ "error": { "code": "invalid_credentials", "message": "Email or password is incorrect." } }`

### POST /auth/logout

No body required. Requires `X-WP-Nonce` header.

**Response**: `{ "data": { "logged_out": true } }`

### GET /auth/me

Called on storefront startup. No auth required (returns `logged_in: false` gracefully if not authenticated).

**Response (logged in)**:
```json
{
  "schema": "storefuse.auth.v1",
  "api_version": "1.0.0",
  "data": {
    "logged_in": true,
    "user": {
      "id": 42,
      "name": "Priya Sharma",
      "email": "user@example.com",
      "avatar_url": "https://gravatar.com/..."
    }
  }
}
```

**Response (not logged in)**:
```json
{ "data": { "logged_in": false } }
```

### POST /auth/forgot-password

Triggers the WordPress password reset email. Does not reveal whether the email exists (prevents user enumeration).

**Body**: `{ "email": "user@example.com" }`

**Response**: `{ "data": { "sent": true } }` - always, regardless of whether account exists.

Internally calls `retrieve_password()`. WordPress sends the email using its own template. Do not write custom email logic.

### POST /auth/reset-password

Completes the password reset using the key from the email link.

**Body**: `{ "key": "abc123", "login": "user@example.com", "password": "newSecurePass" }`

Internally calls `check_password_reset_key()` then `reset_password()`. WordPress core handles validation.

**Response**: `{ "data": { "reset": true } }` or error `invalid_key`.

---

## Customer Module

All endpoints in this section require the user to be logged in. Unauthenticated requests return `401 { "error": { "code": "not_authenticated" } }`.

### GET /account

```json
{
  "schema": "storefuse.customer.v1",
  "api_version": "1.0.0",
  "data": {
    "id": 42,
    "email": "user@example.com",
    "first_name": "Priya",
    "last_name": "Sharma",
    "avatar_url": "https://gravatar.com/...",
    "date_registered": "2026-01-15T10:00:00Z"
  }
}
```

### PUT /account

**Body**: `{ "first_name": "Priya", "last_name": "Patel" }`

**Response**: Updated customer object.

### POST /account/change-password

**Body**: `{ "current_password": "old", "new_password": "newSecure123" }`

Validates current password before accepting the change. Returns `422 { "error": { "code": "invalid_current_password" } }` if current password is wrong.

### GET /orders

Returns the current customer's order history.

**Query params**: `per_page` (default 10), `page` (default 1), `status` (filter by order status)

```json
{
  "schema": "storefuse.orders.v1",
  "api_version": "1.0.0",
  "data": [
    {
      "id": 123,
      "number": "#123",
      "status": "processing",
      "status_label": "Processing",
      "date": "2026-05-18T10:00:00Z",
      "item_count": 2,
      "total": { "raw": 998.00, "formatted": "Rs. 998.00" }
    }
  ],
  "meta": { "total": 12, "page": 1, "per_page": 10 }
}
```

### GET /orders/{id}

Full order detail. Returns 403 if the order belongs to a different customer.

```json
{
  "schema": "storefuse.order.v1",
  "api_version": "1.0.0",
  "data": {
    "id": 123,
    "number": "#123",
    "status": "processing",
    "date": "2026-05-18T10:00:00Z",
    "items": [
      {
        "name": "Diya Set",
        "slug": "diya-set",
        "image": "https://...",
        "qty": 2,
        "price": { "raw": 499.00, "formatted": "Rs. 499.00" },
        "subtotal": { "raw": 998.00, "formatted": "Rs. 998.00" }
      }
    ],
    "totals": {
      "subtotal": { "raw": 998.00, "formatted": "Rs. 998.00" },
      "shipping": { "raw": 0, "formatted": "Free" },
      "tax": { "raw": 0, "formatted": "Rs. 0.00" },
      "discount": { "raw": 100.00, "formatted": "Rs. 100.00" },
      "total": { "raw": 898.00, "formatted": "Rs. 898.00" }
    },
    "payment": { "method": "razorpay", "method_title": "Razorpay", "status": "paid" },
    "tax_lines": [
      { "name": "CGST 9%", "rate": "9.00", "amount": { "raw": 44.91, "formatted": "Rs. 44.91" } },
      { "name": "SGST 9%", "rate": "9.00", "amount": { "raw": 44.91, "formatted": "Rs. 44.91" } }
    ],
    "tracking": { "available": true, "carrier": "Delhivery", "tracking_number": "DL123456" },
    "billing": { "first_name": "Priya", "last_name": "Sharma", "address_1": "...", "city": "Mumbai", "postcode": "400001", "country": "IN", "phone": "+91..." },
    "shipping": { "first_name": "Priya", "last_name": "Sharma", "address_1": "...", "city": "Mumbai", "postcode": "400001", "country": "IN" }
  }
}
```

### POST /orders/{id}/cancel

Cancels a `pending` or `on-hold` order. Returns 422 if order is not in a cancellable state.

### POST /orders/{id}/reorder

Adds all items from a past order into the current cart. Skips items that are no longer available or out of stock. Returns the updated cart.

**Response**: Same shape as `GET /cart`. Includes a `skipped` array listing product names that could not be added.

```json
{ "data": { "cart": { "...cart object..." }, "skipped": ["Discontinued Mug"] } }
```

### POST /orders/{id}/return-request

Submits a return or refund request for a delivered order.

**Body**:
```json
{
  "reason": "Item damaged on arrival",
  "items": [
    { "order_item_id": 55, "quantity": 1 }
  ]
}
```

**Response**: `{ "data": { "submitted": true, "request_id": 78 } }`

Fires `storefuse_bridge_return_request` action. Integrate with a returns plugin or process manually from WooCommerce admin.

### GET /orders/{id}/invoice

Returns a URL to the PDF invoice for the order. Requires the user to own the order.

```json
{ "data": { "available": true, "invoice_url": "https://yourstore.com/wc-api/download-invoice/?order_id=123&key=abc" } }
```

Returns `{ "data": { "available": false } }` if no PDF plugin (e.g. PDF Invoices & Packing Slips) is active. Frontend should hide the download button in that case.

### GET /orders/{id}/tracking

```json
{ "data": { "available": true, "carrier": "Delhivery", "tracking_number": "DL123456", "tracking_url": "https://..." } }
```

Returns `{ "data": { "available": false } }` if no tracking has been set.

### GET /addresses

```json
{
  "data": {
    "billing": { "first_name": "Priya", "last_name": "Sharma", "address_1": "...", "city": "Mumbai", "postcode": "400001", "country": "IN", "phone": "+91..." },
    "shipping": { "first_name": "Priya", "last_name": "Sharma", "address_1": "...", "city": "Mumbai", "postcode": "400001", "country": "IN" }
  }
}
```

### PUT /addresses/billing and PUT /addresses/shipping

**Body**: Address fields object. All fields optional - only provided fields are updated.

### GET /wishlist

Returns saved wishlist items as normalised product objects.

```json
{
  "data": [
    { "id": 56, "slug": "silk-dupatta", "name": "Silk Dupatta", "price": { "raw": 1299.00, "formatted": "Rs. 1,299.00", "currency": "INR", "symbol": "Rs." }, "image": { "url": "https://...", "alt": "Silk Dupatta", "width": 600, "height": 600, "srcset": [] }, "in_stock": true }
  ]
}
```

### POST /wishlist/add

**Body**: `{ "product_id": 56 }`

### DELETE /wishlist/remove

**Body**: `{ "product_id": 56 }`

### GET /downloads

Returns digital product downloads available to the current customer.

```json
{
  "data": [
    {
      "product_name": "StoreFuse Starter Theme",
      "file_name": "storefuse-starter-v1.zip",
      "download_url": "https://...",
      "expires_at": null,
      "downloads_remaining": "unlimited"
    }
  ]
}
```

---

## Checkout Module

### GET /checkout/payment-methods

Returns enabled WooCommerce payment gateways.

```json
{
  "payment_methods": [
    {
      "id": "razorpay",
      "title": "Pay via Razorpay",
      "description": "Credit/Debit cards, UPI, Net banking.",
      "icon_url": "https://yourstore.com/wp-content/plugins/woo-razorpay/razorpay-logo.png",
      "supports": ["products", "refunds"]
    },
    {
      "id": "cod",
      "title": "Cash on Delivery",
      "description": "Pay when your order arrives.",
      "icon_url": null,
      "supports": ["products"]
    }
  ]
}
```

### GET /checkout/shipping-methods

**Query params**: `country`, `state`, `postcode`, `city`  
Returns available shipping rates for the given address.

```json
{
  "shipping_methods": [
    { "id": "free_shipping:1", "label": "Free Shipping",    "cost": { "raw": 0,  "formatted": "Rs. 0.00",  "currency": "INR", "symbol": "Rs." } },
    { "id": "flat_rate:1",    "label": "Standard Shipping", "cost": { "raw": 99, "formatted": "Rs. 99.00", "currency": "INR", "symbol": "Rs." } }
  ]
}
```

### POST /checkout

Submit order. Requires `X-WC-Nonce` header.

**Body:**
```json
{
  "billing": {
    "first_name": "Priya", "last_name": "Sharma",
    "email": "priya@example.com", "phone": "9876543210",
    "address_1": "123 MG Road", "address_2": "",
    "city": "Mumbai", "state": "MH", "postcode": "400001", "country": "IN"
  },
  "shipping": { "...same fields..." },
  "ship_to_different_address": false,
  "payment_method": "razorpay",
  "payment_data": { "razorpay_payment_id": "pay_abc123" },
  "order_comments": ""
}
```

**Response:**
```json
{
  "order_id": 456,
  "order_key": "wc_order_abc123xyz",
  "order_number": "#456",
  "status": "processing",
  "total": { "raw": 998.00, "formatted": "Rs. 998.00", "currency": "INR", "symbol": "Rs." },
  "redirect_url": null,
  "payment_redirect_url": "https://yourstore.com/checkout/order-pay/456/?..."
}
```

### GET /orders/{key}

Order confirmation by order key (public - no login required).

```json
{
  "order_id": 456,
  "order_number": "#456",
  "status": "processing",
  "date": "2026-05-18T14:30:00Z",
  "items": [ { "name": "Handcrafted Diya Set", "quantity": 2, "subtotal": { "raw": 998.00, "formatted": "Rs. 998.00", "currency": "INR", "symbol": "Rs." } } ],
  "totals": {
    "subtotal": { "raw": 998.00, "formatted": "Rs. 998.00", "currency": "INR", "symbol": "Rs." },
    "shipping": { "raw": 0,      "formatted": "Rs. 0.00",   "currency": "INR", "symbol": "Rs." },
    "total":    { "raw": 998.00, "formatted": "Rs. 998.00", "currency": "INR", "symbol": "Rs." }
  },
  "billing": { "first_name": "Priya", "city": "Mumbai", "country": "IN" },
  "payment_method": "razorpay",
  "payment_method_title": "Razorpay"
}
```

---

## Utils Module

### GET /utils/countries

Returns all countries and their states/provinces. Used to populate country and state dropdowns in checkout and account address forms. Without this, forms that require state selection (India, US, etc.) cannot work.

**Cache**: 24 hours (never changes).

```json
{
  "schema": "storefuse.countries.v1",
  "api_version": "1.0.0",
  "data": [
    {
      "code": "IN",
      "name": "India",
      "states": [
        { "code": "MH", "name": "Maharashtra" },
        { "code": "DL", "name": "Delhi" },
        { "code": "KA", "name": "Karnataka" }
      ]
    },
    {
      "code": "US",
      "name": "United States",
      "states": [
        { "code": "CA", "name": "California" },
        { "code": "NY", "name": "New York" }
      ]
    }
  ]
}
```

Data comes from WooCommerce: `WC()->countries->get_countries()` and `WC()->countries->get_states()`. Always in sync with WC settings.

### GET /utils/pincode/{pincode}

Lookup city and state from an Indian pincode. Used to auto-fill city and state when a user enters their pincode in the checkout address form.

```json
{ "data": { "city": "Mumbai", "state": "Maharashtra", "state_code": "MH", "country": "IN" } }
```

Returns `404 { "error": { "code": "pincode_not_found" } }` if the pincode is invalid.

Uses a static pincode dataset bundled with the plugin (India Post data). No external API call. Fires `storefuse_bridge_pincode_lookup` filter for stores that want to override with a live API (e.g. Shiprocket pincode service).

---

## Content Module

### GET /reviews

**Query params**: `product_id` (required), `per_page` (default 10), `page`

```json
{
  "reviews": [
    {
      "id": 99,
      "author": "Priya M.",
      "rating": 5,
      "comment": "The karwa chauth set is absolutely beautiful!",
      "date": "2026-04-12T10:00:00Z",
      "verified_buyer": true
    }
  ],
  "average_rating": 4.8,
  "total": 42
}
```

### POST /reviews

Submit a product review. Honeypot anti-spam included.

**Body**: `{ "product_id": 123, "rating": 5, "comment": "...", "author": "Priya", "email": "priya@example.com" }`  
**Returns**: `{ "status": "pending" | "approved", "message": "..." }`

### GET /posts

Blog posts.  
**Query params**: `per_page`, `page`, `category`

```json
{
  "posts": [
    {
      "id": 77, "slug": "diwali-decorating-tips",
      "title": "5 Diwali Decorating Tips", "excerpt": "...",
      "date": "2026-10-01T00:00:00Z",
      "image": { "url": "https://yourstore.com/wp-content/uploads/diwali.jpg", "alt": "5 Diwali Decorating Tips", "width\": 1200, \"height\": 630, \"srcset\": [] },
      "categories": [{ "name": "Tips", "slug": "tips" }],
      "author": "Admin"
    }
  ],
  "total": 12, "total_pages": 2
}
```

### GET /posts/{slug}

Single post with full HTML content.

---

## Error Responses

All errors follow WordPress REST API conventions:

```json
{
  "code": "product_not_found",
  "message": "No product found with slug: invalid-slug",
  "data": { "status": 404 }
}
```

**Common error codes:**

| Code | HTTP | Meaning |
|---|---|---|
| `woocommerce_not_active` | 500 | WooCommerce is not active |
| `module_disabled` | 404 | Requested module is disabled in plugin settings |
| `product_not_found` | 404 | Product slug does not exist |
| `category_not_found` | 404 | Category slug does not exist |
| `out_of_stock` | 400 | Cannot add out-of-stock product to cart |
| `invalid_coupon` | 400 | Coupon code does not exist or has expired |
| `invalid_nonce` | 403 | Cart/checkout nonce missing or invalid |
| `search_query_too_short` | 400 | Search query must be at least 2 characters |

---

## Caching Summary

| Endpoint | Cache TTL | Invalidated by |
|---|---|---|
| `/status` | none | - |
| `/settings` | 1 hour | Admin saves settings |
| `/navigation` | 1 hour | Nav menu updated |
| `/homepage` | 15 min | Admin saves settings |
| `/products` | 10 min | Product saved |
| `/products/{slug}` | 10 min | Product saved |
| `/categories` | 1 hour | Category saved |
| `/categories/{slug}` | 10 min | Category saved |
| `/search` | 5 min | Product saved |
| `/cart` | none | Always live |
| `/checkout/*` | none | Always live |
| `/reviews` | 30 min | Review posted/approved |
| `/posts` | 1 hour | Post published/updated |


All endpoints are **public, read-only, no authentication required**.  
All responses are `Content-Type: application/json`.  
CORS is open for all origins (configurable in plugin settings).

---

## GET /status

Health check. Confirms the plugin is active and returns version info.

**Request**
```
GET /wp-json/storefuse/v1/status
```

**Response**
```json
{
  "status": "ok",
  "plugin": "StoreFuse Bridge",
  "version": "0.1.0",
  "wordpress": "6.5.3",
  "woocommerce": "9.0.0",
  "site_url": "https://yourstore.com",
  "api_namespace": "storefuse/v1"
}
```

---

## GET /settings

The main endpoint. Returns all site identity, store configuration, navigation, and social data in a single request. The StoreFuse storefront calls this once at startup.

**Request**
```
GET /wp-json/storefuse/v1/settings
```

**Response**
```json
{
  "site": {
    "name": "My Festive Store",
    "tagline": "Handcrafted for modern homes",
    "url": "https://yourstore.com",
    "logo_url": "https://yourstore.com/wp-content/uploads/2024/logo.png",
    "favicon_url": "https://yourstore.com/wp-content/uploads/2024/favicon.ico",
    "admin_email": "admin@yourstore.com"
  },
  "store": {
    "currency": "INR",
    "currency_symbol": "₹",
    "currency_position": "left",
    "price_decimal_separator": ".",
    "price_thousand_separator": ",",
    "price_decimals": 0,
    "free_shipping_threshold": 999,
    "free_shipping_label": "Free shipping on orders over ₹999",
    "return_policy_days": 7,
    "cod_enabled": true
  },
  "header": {
    "announcement_bar_enabled": true,
    "announcement_bar_text": "🚚 Free shipping on orders over ₹999 · Easy 7-day returns",
    "announcement_bar_bg_color": "#E85D04"
  },
  "navigation": {
    "main": [
      { "id": 1, "label": "Home", "href": "/", "parent": null },
      { "id": 2, "label": "Shop", "href": "/shop", "parent": null },
      { "id": 3, "label": "New Arrivals", "href": "/shop?sort=newest", "parent": null },
      { "id": 4, "label": "Sale", "href": "/shop?on_sale=true", "parent": null }
    ],
    "categories": [
      {
        "id": 12,
        "label": "Festive Decor",
        "slug": "festive-decor",
        "href": "/category/festive-decor",
        "icon": "🪔",
        "image": { "url": "https://yourstore.com/wp-content/uploads/festive-decor.jpg", "alt": "Festive Decor", "width": 800, "height": 800, "srcset": [] }
      },
      {
        "id": 15,
        "label": "Diyas & Lamps",
        "slug": "diyas",
        "href": "/category/diyas",
        "icon": "✨",
        "image_url": null
      }
    ],
    "footer": [
      { "id": 10, "label": "Shop", "href": "/shop", "parent": null },
      { "id": 11, "label": "Cart", "href": "/cart", "parent": null }
    ]
  },
  "trust_badges": [
    { "icon": "🚚", "title": "Free Shipping", "description": "On orders over ₹999" },
    { "icon": "↩", "title": "Easy Returns", "description": "7-day hassle-free returns" },
    { "icon": "🔒", "title": "Secure Payment", "description": "100% protected checkout" },
    { "icon": "🤝", "title": "Handmade Quality", "description": "Authentic artisan products" }
  ],
  "social_links": {
    "instagram": "https://instagram.com/yourstore",
    "facebook": "https://facebook.com/yourstore",
    "twitter": null,
    "youtube": null,
    "pinterest": null,
    "whatsapp": null
  }
}
```

---

## GET /navigation

Navigation menus only. Use this if you only need to refresh nav without re-fetching all settings.

**Request**
```
GET /wp-json/storefuse/v1/navigation
```

**Response**
```json
{
  "main": [
    { "id": 1, "label": "Home", "href": "/", "parent": null, "target": "_self" },
    { "id": 2, "label": "Shop", "href": "/shop", "parent": null, "target": "_self" }
  ],
  "categories": [
    {
      "id": 12,
      "label": "Festive Decor",
      "slug": "festive-decor",
      "href": "/category/festive-decor",
      "icon": "🪔",
      "image_url": "https://yourstore.com/..."
    }
  ],
  "footer": [
    { "id": 10, "label": "Shop", "href": "/shop", "parent": null }
  ]
}
```

**Notes**
- `main` comes from the WordPress nav menu assigned to the `storefuse-header` location. Fallback is auto-generated from WooCommerce categories.
- `categories` comes from top-level WooCommerce product categories (up to 8, ordered by menu_order).
- `footer` comes from the WordPress nav menu assigned to the `storefuse-footer` location.

---

## GET /homepage

Homepage-specific configuration: hero content, featured categories (admin-curated), announcement bar state.

**Request**
```
GET /wp-json/storefuse/v1/homepage
```

**Response**
```json
{
  "announcement_bar": {
    "enabled": true,
    "text": "🪔 New Festive Collection 2026 - Shop now",
    "bg_color": "#E85D04",
    "link": "/shop?tag=new-2026"
  },
  "hero": {
    "badge_text": "🪔 New Festive Collection 2026",
    "headline": "Handcrafted decor for modern homes",
    "headline_highlight": "modern homes",
    "subheadline": "Discover artisan-made festive pieces, diyas, jewelry and home accents.",
    "cta_primary_label": "Shop Now",
    "cta_primary_href": "/shop",
    "cta_secondary_label": "New Arrivals",
    "cta_secondary_href": "/shop?sort=newest",
    "image_url": null,
    "rating_text": "4.8/5 from 2,400+ reviews",
    "shipping_text": "Free shipping over ₹999"
  },
  "featured_categories": [
    {
      "label": "Festive Decor",
      "icon": "🪔",
      "href": "/category/festive-decor",
      "slug": "festive-decor",
      "color_class": "bg-orange-50 hover:bg-orange-100"
    }
  ],
  "trust_items": [
    { "icon": "🤝", "title": "Handmade Quality", "description": "Every piece crafted by skilled artisans" },
    { "icon": "🚚", "title": "Fast Shipping", "description": "Delivered in 3–5 business days" },
    { "icon": "🔒", "title": "Secure Payments", "description": "SSL encrypted, 100% safe" },
    { "icon": "↩", "title": "Easy Returns", "description": "7-day no-questions-asked policy" }
  ]
}
```

---

## Caching

The StoreFuse storefront caches these responses using Next.js fetch cache:

| Endpoint | Recommended revalidate |
|---|---|
| `/settings` | 3600 (1 hour) |
| `/navigation` | 3600 (1 hour) |
| `/homepage` | 900 (15 minutes) |
| `/status` | no-cache |

When you save settings in the WordPress admin, the plugin can optionally trigger an ISR revalidation webhook to your Next.js app (future feature, v1.1).

---

## Error Responses

All error responses follow WordPress REST API conventions:

```json
{
  "code": "storefuse_bridge_not_active",
  "message": "WooCommerce is required but not active.",
  "data": { "status": 500 }
}
```

| Code | HTTP Status | Meaning |
|---|---|---|
| `storefuse_bridge_not_active` | 500 | WooCommerce not active |
| `rest_forbidden` | 403 | Endpoint restricted (not expected in normal use) |
| `rest_no_route` | 404 | Plugin not installed or namespace wrong |
