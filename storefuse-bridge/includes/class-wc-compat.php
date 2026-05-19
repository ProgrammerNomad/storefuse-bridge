<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce version and feature compatibility detection.
 *
 * The storefront reads the 'features' map from GET /status to decide
 * runtime behaviour — no hardcoded capability checks in the frontend.
 */
class StoreFuse_Bridge_WC_Compat {

    /**
     * Build the full features capability map for the status endpoint.
     *
     * @return array<string, bool>
     */
    public static function features(): array {
        return [
            'hpos'                => self::has_hpos(),
            'store_api'           => self::has_store_api(),
            'headless_checkout'   => false, // future milestone
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
}
