<?php
defined( 'ABSPATH' ) || exit;

/**
 * Transient-based cache helpers.
 *
 * All transient keys are prefixed with 'sfb_' to allow bulk-flushing
 * without touching other plugins' transients.
 */
class StoreFuse_Bridge_Cache {

    private const PREFIX = 'sfb_';

    /**
     * Get cached data. Returns null on miss.
     *
     * @param string $key  Cache key (without prefix)
     */
    public static function get( string $key ): mixed {
        $value = get_transient( self::PREFIX . $key );
        return $value === false ? null : $value;
    }

    /**
     * Store data in the cache.
     *
     * @param string $key        Cache key (without prefix)
     * @param mixed  $value      Data to store
     * @param int    $expiry     Expiry in seconds (default 10 minutes)
     */
    public static function set( string $key, mixed $value, int $expiry = 600 ): void {
        set_transient( self::PREFIX . $key, $value, $expiry );

        // Track the key so we can flush it later
        $keys   = (array) get_option( 'storefuse_bridge_cache_keys', [] );
        $keys[] = self::PREFIX . $key;
        update_option( 'storefuse_bridge_cache_keys', array_unique( $keys ), false );
    }

    /**
     * Delete a single cache entry.
     */
    public static function delete( string $key ): void {
        delete_transient( self::PREFIX . $key );
    }

    /**
     * Flush every StoreFuse Bridge transient.
     * Called on deactivation and via the admin flush button.
     */
    public static function flush_all(): void {
        $keys = (array) get_option( 'storefuse_bridge_cache_keys', [] );
        foreach ( $keys as $full_key ) {
            // $full_key already includes the prefix because we stored it that way
            delete_transient( ltrim( str_replace( self::PREFIX, '', $full_key ), '_' ) );
            // Also try deleting with the raw full key (covers both paths)
            delete_transient( $full_key );
        }
        delete_option( 'storefuse_bridge_cache_keys' );
    }

    /**
     * Cache key for a product. Invalidated by woocommerce_update_product hook.
     */
    public static function product_key( string $slug ): string {
        return 'product_' . sanitize_key( $slug );
    }

    /**
     * Cache key for a product list query.
     *
     * @param array $params Query params (sorted before hashing for consistency)
     */
    public static function products_list_key( array $params ): string {
        ksort( $params );
        return 'products_' . md5( serialize( $params ) );
    }

    /**
     * Cache key for category data.
     */
    public static function category_key( string $slug ): string {
        return 'cat_' . sanitize_key( $slug );
    }

    /**
     * Cache key for the full category tree.
     */
    public static function categories_key(): string {
        return 'categories_all';
    }

    /**
     * Cache key for site settings.
     */
    public static function settings_key(): string {
        return 'settings_v1';
    }

    /**
     * Cache key for navigation menus.
     */
    public static function navigation_key(): string {
        return 'navigation_v1';
    }

    /**
     * Cache key for homepage config.
     */
    public static function homepage_key(): string {
        return 'homepage_v1';
    }

    // ── Auto-invalidation hooks ───────────────────────────────────────────────

    /**
     * Register WooCommerce/WordPress hooks that bust the cache automatically.
     * Called once from the main plugin class.
     */
    public static function register_invalidation_hooks(): void {
        // Products
        add_action( 'woocommerce_update_product', [ self::class, 'invalidate_products' ] );
        add_action( 'woocommerce_new_product',    [ self::class, 'invalidate_products' ] );
        add_action( 'woocommerce_delete_product', [ self::class, 'invalidate_products' ] );
        add_action( 'woocommerce_product_set_stock',        [ self::class, 'invalidate_products' ] );
        add_action( 'woocommerce_product_set_stock_status', [ self::class, 'invalidate_products' ] );

        // Categories
        add_action( 'created_product_cat', [ self::class, 'invalidate_categories' ] );
        add_action( 'edited_product_cat',  [ self::class, 'invalidate_categories' ] );
        add_action( 'deleted_product_cat', [ self::class, 'invalidate_categories' ] );

        // Settings / nav
        add_action( 'customize_save_after',      [ self::class, 'invalidate_settings' ] );
        add_action( 'wp_update_nav_menu',        [ self::class, 'invalidate_navigation' ] );
        add_action( 'update_option_storefuse_bridge_settings', [ self::class, 'invalidate_settings' ] );
    }

    public static function invalidate_products(): void {
        // We don't know exactly which list query caches are affected, so flush all product transients
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_sfb_product_%',
                '_transient_sfb_products_%'
            )
        );
    }

    public static function invalidate_categories(): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_sfb_cat_%',
                '_transient_sfb_categories%'
            )
        );
    }

    public static function invalidate_settings(): void {
        delete_transient( self::PREFIX . self::settings_key() );
        delete_transient( self::PREFIX . self::homepage_key() );
    }

    public static function invalidate_navigation(): void {
        delete_transient( self::PREFIX . self::navigation_key() );
        delete_transient( self::PREFIX . self::settings_key() ); // settings includes nav
    }
}
