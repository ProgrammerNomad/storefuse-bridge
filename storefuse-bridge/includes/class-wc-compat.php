<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce version and feature compatibility detection.
 *
 * The storefront reads the 'features' map from GET /status to decide
 * runtime behaviour - no hardcoded capability checks in the frontend.
 */
class StoreFuse_Bridge_WC_Compat {

    /**
     * Build the full features capability map for the status endpoint.
     *
     * @return array<string, bool>
     */
    public static function features(): array {
        return [
            'registration'        => get_option( 'woocommerce_enable_myaccount_registration', 'no' ) === 'yes',
            'account_deletion'    => true,
            'pincode_serviceability' => (bool) apply_filters( 'storefuse_bridge_pincode_serviceability_available', false ),
            'stock_alerts'        => (bool) apply_filters( 'storefuse_bridge_stock_alerts_available', false ),
            'returns'             => (bool) apply_filters( 'storefuse_bridge_returns_available', false ),
            'invoice_download'    => (bool) apply_filters( 'storefuse_bridge_invoice_available', false ),
            'push_notifications'  => (bool) apply_filters( 'storefuse_bridge_push_available', false ),
            'hpos'                => self::has_hpos(),
            'store_api'           => self::has_store_api(),
            'headless_checkout'   => self::headless_checkout_enabled(),
            'subscriptions'       => self::has_subscriptions(),
            'memberships'         => self::has_memberships(),
            'wpml'                => self::has_wpml(),
            'polylang'            => self::has_polylang(),
            'yoast_seo'           => self::has_yoast_seo(),
            'rank_math'           => self::has_rank_math(),
            'acf'                 => self::has_acf(),
        ];
    }

    // ── WooCommerce core ─────────────────────────────────────────────────────

    /**
     * Whether WooCommerce HPOS (High-Performance Order Storage) is active.
     */
    public static function has_hpos(): bool {
        if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Whether the WC Store API (wc/store/v1) is available.
     */
    public static function has_store_api(): bool {
        return class_exists( 'Automattic\WooCommerce\StoreApi\StoreApi' );
    }

    /**
     * Whether headless checkout is active (checkout module + headless mode).
     */
    public static function headless_checkout_enabled(): bool {
        if ( ! (bool) StoreFuse_Bridge_Settings::get( 'module_checkout_enabled', true ) ) {
            return false;
        }
        return StoreFuse_Bridge_Settings::get( 'module_cart_enabled', true ) && StoreFuse_Bridge_Settings::get( 'checkout_mode', 'redirect' ) === 'headless';
    }

    // ── WooCommerce extensions ───────────────────────────────────────────────

    public static function has_subscriptions(): bool {
        return class_exists( 'WC_Subscriptions' );
    }

    public static function has_memberships(): bool {
        return class_exists( 'WC_Memberships' );
    }

    // ── Multilingual plugins ─────────────────────────────────────────────────

    public static function has_wpml(): bool {
        return defined( 'ICL_SITEPRESS_VERSION' );
    }

    public static function has_polylang(): bool {
        return function_exists( 'pll_current_language' );
    }

    // ── SEO plugins ──────────────────────────────────────────────────────────

    public static function has_yoast_seo(): bool {
        return defined( 'WPSEO_VERSION' );
    }

    public static function has_rank_math(): bool {
        return defined( 'RANK_MATH_VERSION' );
    }

    // ── Content plugins ──────────────────────────────────────────────────────

    public static function has_acf(): bool {
        return function_exists( 'get_field' );
    }

    // ── Version helpers ──────────────────────────────────────────────────────

    public static function wc_version(): string {
        return defined( 'WC_VERSION' ) ? WC_VERSION : '0.0.0';
    }

    public static function wp_version(): string {
        global $wp_version;
        return $wp_version ?? '0.0.0';
    }

    public static function woocommerce_active(): bool {
        return class_exists( 'WooCommerce' ) && self::wc_version() !== '0.0.0';
    }

    /**
     * Cheap DB connectivity probe for GET /status health.
     */
    public static function database_ok(): bool {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return false;
        }
        if ( method_exists( $wpdb, 'check_connection' ) ) {
            return (bool) $wpdb->check_connection();
        }
        return true;
    }

    /**
     * Runtime health snapshot for GET /status (storefront probes).
     *
     * @return array<string, mixed>
     */
    public static function health_snapshot(): array {
        $wc_active = self::woocommerce_active();
        $wc_ok     = $wc_active && version_compare( self::wc_version(), STOREFUSE_BRIDGE_MIN_WC, '>=' );
        $wp_ok     = version_compare( self::wp_version(), STOREFUSE_BRIDGE_MIN_WP, '>=' );
        $php_ok    = version_compare( PHP_VERSION, STOREFUSE_BRIDGE_MIN_PHP, '>=' );
        $db_ok     = self::database_ok();

        $currency = function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : '';

        return [
            'ok'                   => $wc_ok && $wp_ok && $php_ok && $db_ok,
            'woocommerce_active'   => $wc_active,
            'database_ok'          => $db_ok,
            'checked_at'           => gmdate( 'c' ),
            'requirements'         => [
                'wordpress'   => [
                    'required' => STOREFUSE_BRIDGE_MIN_WP,
                    'current'  => self::wp_version(),
                    'ok'       => $wp_ok,
                ],
                'woocommerce' => [
                    'required' => STOREFUSE_BRIDGE_MIN_WC,
                    'current'  => self::wc_version(),
                    'active'   => $wc_active,
                    'ok'       => $wc_ok,
                ],
                'php'         => [
                    'required' => STOREFUSE_BRIDGE_MIN_PHP,
                    'current'  => PHP_VERSION,
                    'ok'       => $php_ok,
                ],
            ],
            'store'                => [
                'currency'     => $currency,
                'locale'       => function_exists( 'get_locale' ) ? get_locale() : '',
                'multilingual' => [
                    'wpml'     => self::has_wpml(),
                    'polylang' => self::has_polylang(),
                ],
            ],
        ];
    }
}
