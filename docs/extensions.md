# StoreFuse Bridge - extension hooks (v0.1.0)

Companion WordPress plugins extend StoreFuse Bridge **without editing core**. Use filters to reshape JSON; use actions to react to lifecycle events.

**Minimum requirements for a companion plugin:**

```php
/**
 * Plugin Name: My Store - StoreFuse Bridge extensions
 * Requires Plugins: woocommerce, storefuse-bridge
 */
if ( ! defined( 'STOREFUSE_BRIDGE_VERSION' ) ) {
    return;
}
if ( version_compare( STOREFUSE_BRIDGE_VERSION, '0.1.0', '<' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>Requires StoreFuse Bridge 0.1.0+.</p></div>';
    } );
    return;
}
```

After Bridge updates, re-test critical filters (product, cart, checkout) on staging.

---

## Filters (response and data shaping)

| Hook | Arguments | Return | Source |
|------|-----------|--------|--------|
| `storefuse_bridge_status_response` | `$data` (array), `$request` | array | Status module |
| `storefuse_bridge_settings_response` | `$data`, `$request` | array | Settings |
| `storefuse_bridge_navigation_response` | `$data`, `$request` | array | Settings |
| `storefuse_bridge_homepage_response` | `$data`, `$request` | array | Settings |
| `storefuse_bridge_products_response` | `$data`, `$request` | array | Products list |
| `storefuse_bridge_product_response` | `$data`, `$product` (WC_Product), `$request` | array | Single product |
| `storefuse_bridge_categories_response` | `$data`, `$request` | array | Categories list |
| `storefuse_bridge_category_response` | `$data`, `$term` (WP_Term), `$request` | array | Single category |
| `storefuse_bridge_search_response` | `$data`, `$request` | array | Search |
| `storefuse_bridge_attribute_data` | `$attribute` (array) | array | Attributes |
| `storefuse_bridge_tag_data` | `$tag` (array) | array | Tags |
| `storefuse_bridge_post_summary` | `$summary` (array) | array | Posts list item |
| `storefuse_bridge_post_extras` | `$extras` (array) | array | Single post |
| `storefuse_bridge_post_seo` | `$seo` (array) | array | Single post |
| `storefuse_bridge_review_data` | `$review` (array) | array | Reviews |
| `storefuse_bridge_cart_data` | `$cart` (array) | array | Cart payload |
| `storefuse_bridge_cart_item` | `$item` (array), `$wc_cart_item` | array | Each cart line |
| `storefuse_bridge_checkout_config` | `$config` (array) | array | Checkout config |
| `storefuse_bridge_payment_methods` | `$methods` (array) | array | Payment methods list |
| `storefuse_bridge_shipping_packages` | `$packages` (array) | array | Shipping packages |
| `storefuse_bridge_checkout_result` | `$result` (array) | array | After order placed |
| `storefuse_bridge_checkout_redirect_url` | `$url` (string) | string | Redirect checkout URL |
| `storefuse_bridge_order_data` | `$order` (array), `$wc_order` (WC_Order) | array | Order shape (checkout + format) |
| `storefuse_bridge_user_data` | `$user_data` (array), `$user` (WP_User) | array | Auth user payload |
| `storefuse_bridge_account_data` | `$account` (array) | array | Account module |
| `storefuse_bridge_order_tracking` | `$tracking` (array), `$order` | array | Orders |
| `storefuse_bridge_order_invoice` | `$invoice` (array), `$order` | array | Orders |
| `storefuse_bridge_invoice_print_url` | `$url`, `$order` | string | Invoice print link |
| `storefuse_bridge_wishlist_item` | `$item` (array) | array | Wishlist |
| `storefuse_bridge_download_item` | `$item` (array) | array | Downloads |
| `storefuse_bridge_calling_codes` | `$codes` (array) | array | Utils countries |
| `storefuse_bridge_pincode_lookup` | `$override` (null\|array), `$pincode` (string) | null\|array | Return array to replace pincode lookup |
| `storefuse_bridge_password_reset_url` | `$reset_url`, `$user`, `$key` | string | Password reset email link |

### Example: add ACF fields to product JSON

```php
add_filter( 'storefuse_bridge_product_response', function ( array $data, WC_Product $product, WP_REST_Request $request ): array {
    if ( function_exists( 'get_field' ) ) {
        $data['acf'] = [
            'care_instructions' => get_field( 'care_instructions', $product->get_id() ),
        ];
    }
    return $data;
}, 10, 3 );
```

### Example: storefront password reset (mobile deep link)

```php
add_filter( 'storefuse_bridge_password_reset_url', function ( string $url, WP_User $user, string $key ): string {
    return add_query_arg(
        [ 'key' => rawurlencode( $key ), 'login' => rawurlencode( $user->user_login ) ],
        'myapp://reset-password'
    );
}, 10, 3 );
```

### Example: custom checkout redirect (Wholesale plugin)

```php
add_filter( 'storefuse_bridge_checkout_redirect_url', function ( string $url ): string {
    if ( function_exists( 'my_wholesale_checkout_url' ) ) {
        return my_wholesale_checkout_url();
    }
    return $url;
} );
```

---

## Actions (events)

| Hook | Arguments | When |
|------|-----------|------|
| `storefuse_bridge_settings_updated` | none | Admin saves Bridge settings (cache flush, webhooks) |
| `storefuse_bridge_guest_cart_merged` | `$user_id`, `$guest_items` (array) | After login merges guest cart |
| `storefuse_bridge_notify_signup` | `$email`, `$product` (WC_Product) | Back-in-stock notify POST |
| `storefuse_bridge_order_return_requested` | `$order` (WC_Order), `$reason` (string) | Customer return request |

### Example: analytics on cart merge

```php
add_action( 'storefuse_bridge_guest_cart_merged', function ( int $user_id, array $guest_items ): void {
    // $guest_items: line items that were in the guest session before merge
}, 10, 2 );
```

---

## Upgrade checklist (store owners)

1. Note current `STOREFUSE_BRIDGE_VERSION` and companion plugin versions.
2. Update Bridge on staging; hit `GET /status` and confirm `api_version` / modules.
3. Smoke-test: product detail filter output, cart add, checkout config, login.
4. Deploy to production; flush object cache if used.

---

## v0.2 module registration

Not available in v0.1.0. See [extension-api-v0.2.md](extension-api-v0.2.md) for the planned `storefuse_bridge_modules` filter and companion module contract.

---

## Admin documentation URL

| Filter | Arguments | Return | Purpose |
|--------|-----------|--------|---------|
| `storefuse_bridge_documentation_url` | `$url` (string) | string | Override the GitHub docs index URL shown in wp-admin (default: `STOREFUSE_BRIDGE_DOCS_URL` constant). |
