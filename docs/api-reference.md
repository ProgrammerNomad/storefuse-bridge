# API Reference - StoreFuse Bridge

**Base URL**: `https://your-wordpress-site.com/wp-json/storefuse/v1`

**All endpoints are public and read-only** unless noted otherwise.  
Cart and checkout endpoints require a WC nonce (issued by `GET /cart`).  
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

**Standard response headers on every request:**
```
X-StoreFuse-Bridge-Version: 1.0.0
X-StoreFuse-Cache: HIT | MISS
Cache-Control: public, max-age=600
```

**Data normalisation guarantee** - no WordPress or WooCommerce internal concepts appear in responses. No `post_meta` keys, no taxonomy IDs, no ACF field names. The plugin normalises everything into clean, semantically named fields. Third-party integrations (SEO, translations, custom fields) inject data under normalised keys via extension filters.

---

## Status

### GET /status

Health check. Confirms plugin is active and lists available modules.

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
    }
  }
}
```

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
      "image_url": "https://yourstore.com/wp-content/uploads/hero.jpg",
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
        "price": "₹499",
        "price_raw": 499,
        "regular_price": "₹699",
        "regular_price_raw": 699,
        "sale_price": "₹499",
        "on_sale": true,
        "stock_status": "instock",
        "stock_quantity": 24,
        "sku": "DIYA-001",
        "images": [
          { "id": "1", "src": "https://yourstore.com/wp-content/uploads/diya.jpg", "alt": "Diya Set" }
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
      "price": "₹499",
      "price_raw": 499,
      "regular_price": "₹699",
      "regular_price_raw": 699,
      "sale_price": "₹499",
      "on_sale": true,
      "stock_status": "instock",
      "stock_quantity": 24,
      "sku": "DIYA-001",
      "images": [
        { "id": "1", "src": "https://yourstore.com/wp-content/uploads/diya.jpg", "alt": "Diya Set" }
      ],
      "categories": [
        { "id": "12", "name": "Festive Decor", "slug": "festive-decor" }
      ],
      "attributes": [],
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
      "image_url": "https://yourstore.com/wp-content/uploads/festive.jpg",
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
      "image_url": "https://yourstore.com/...",
      "price": "₹499",
      "price_raw": 499,
      "line_subtotal": "₹998",
      "line_subtotal_raw": 998
    }
  ],
  "totals": {
    "subtotal": "₹998",
    "subtotal_raw": 998,
    "discount": "₹0",
    "discount_raw": 0,
    "shipping": "₹0",
    "shipping_raw": 0,
    "tax": "₹0",
    "tax_raw": 0,
    "total": "₹998",
    "total_raw": 998
  },
  "coupons": [],
  "item_count": 2,
  "shipping_methods": [
    { "id": "free_shipping", "label": "Free Shipping", "cost": "₹0" }
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
    { "id": "free_shipping:1", "label": "Free Shipping", "cost": "₹0", "cost_raw": 0 },
    { "id": "flat_rate:1", "label": "Standard Shipping", "cost": "₹99", "cost_raw": 99 }
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
  "total": "₹998",
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
  "items": [ { "name": "Handcrafted Diya Set", "quantity": 2, "subtotal": "₹998" } ],
  "totals": { "subtotal": "₹998", "shipping": "₹0", "total": "₹998" },
  "billing": { "first_name": "Priya", "city": "Mumbai", "country": "IN" },
  "payment_method": "razorpay",
  "payment_method_title": "Razorpay"
}
```

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
      "image_url": "https://yourstore.com/wp-content/uploads/diwali.jpg",
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
        "image_url": "https://yourstore.com/wp-content/uploads/festive-decor.jpg"
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
