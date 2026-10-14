<?php
defined( 'ABSPATH' ) || exit;

/**
 * Transient-based cache helpers.
 *
 * Content entries have a dedicated, versioned namespace. Security and session
 * records are never invalidated by these helpers.
 */
class StoreFuse_Bridge_Cache {

    private const PREFIX = 'sfb_content_';

    private static function key( string $key ): string {
        $generation = (string) get_option( 'sfb_content_generation', '0' );
        return self::PREFIX . $generation . '_' . md5( $key );
    }

    /**
     * Get cached data. Returns null on miss.
     *
     * @param string $key  Cache key (without prefix)
     */
    public static function get( string $key ): mixed {
        $value = get_transient( self::key( $key ) );
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
        set_transient( self::key( $key ), $value, $expiry );
    }

    /**
     * Delete a single cache entry.
     */
    public static function delete( string $key ): void {
        delete_transient( self::key( $key ) );
    }

    /**
     * Invalidate StoreFuse content caches only.
     * Called on deactivation and via the admin flush button.
     */
    public static function flush_all(): void {
        // Generation changes invalidate Redis/Memcached too, without touching
        // security counters, cart credentials or checkout recovery records.
        update_option( 'sfb_content_generation', bin2hex( random_bytes( 12 ) ), false );
        self::record_flush( 'all' );
    }

    /**
     * Flush a logical cache group (admin tools).
     *
     * @param string $group all|products|settings|navigation|homepage|search
     */
    public static function flush_group( string $group ): void {
        switch ( $group ) {
            case 'products':
                self::invalidate_products();
                break;
            case 'settings':
                self::invalidate_settings();
                self::delete( self::settings_key() );
                break;
            case 'navigation':
                self::invalidate_navigation();
                break;
            case 'homepage':
                self::delete( self::homepage_key() );
                break;
            case 'search':
                // Search reads live product data; bust product list caches.
                self::invalidate_products();
                break;
            case 'all':
            default:
                self::flush_all();
                return;
        }
        self::record_flush( $group );
    }

    /**
     * Persist last manual flush timestamp for the dashboard.
     */
    public static function record_flush( string $group ): void {
        update_option(
            'storefuse_bridge_last_flush_at',
            [
                'time'  => current_time( 'mysql' ),
                'group' => $group,
            ],
            false
        );
    }

    public static function search_key_prefix(): string {
        return 'search_';
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
        self::flush_all();
    }

    public static function invalidate_categories(): void {
        self::flush_all();
    }

    public static function invalidate_settings(): void {
        self::delete( self::settings_key() );
        self::delete( self::homepage_key() );
    }

    public static function invalidate_navigation(): void {
        self::delete( self::navigation_key() );
        self::delete( self::settings_key() ); // settings includes nav
    }
}
