<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings Module
 *
 * GET /storefuse/v1/settings   - full site identity + store config + nav + social
 * GET /storefuse/v1/navigation - navigation menus only
 * GET /storefuse/v1/homepage   - homepage content block config
 *
 * All three endpoints are public and cached.
 * Cache is auto-invalidated by WC/WP save hooks registered in StoreFuse_Bridge_Cache.
 */
class StoreFuse_Bridge_Module_Settings extends StoreFuse_Bridge_Module {

    protected string $id = 'settings';

    // Cache durations
    private const SETTINGS_TTL  = 3600;  // 1 hour
    private const NAV_TTL       = 3600;  // 1 hour
    private const HOMEPAGE_TTL  = 900;   // 15 minutes

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/settings', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'public_permission' ],
        ] );

        register_rest_route( $this->namespace, '/navigation', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_navigation' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'public_permission' ],
        ] );

        register_rest_route( $this->namespace, '/homepage', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_homepage' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'public_permission' ],
        ] );
    }

    // ── GET /settings ────────────────────────────────────────────────────────

    public function get_settings( WP_REST_Request $request ): WP_REST_Response {
        $cached = StoreFuse_Bridge_Cache::get( StoreFuse_Bridge_Cache::settings_key() );

        if ( $cached !== null ) {
            $response = $this->success( $cached, 'storefuse.settings.v1' );
            StoreFuse_Bridge_Response::mark_cache_hit( $response );
            return StoreFuse_Bridge_Response::with_public_cache( $response, self::SETTINGS_TTL );
        }

        $data = [
            'site'         => $this->build_site_identity(),
            'store'        => $this->build_store_config(),
            'header'       => $this->build_header_config(),
            'trust_badges' => $this->build_trust_badges(),
            'social_links' => $this->build_social_links(),
            'navigation'   => $this->build_navigation(),
        ];

        /**
         * Filter the full settings response.
         *
         * @param array           $data
         * @param WP_REST_Request $request
         */
        $data = apply_filters( 'storefuse_bridge_settings_response', $data, $request );

        StoreFuse_Bridge_Cache::set( StoreFuse_Bridge_Cache::settings_key(), $data, self::SETTINGS_TTL );

        $response = $this->success( $data, 'storefuse.settings.v1' );
        return StoreFuse_Bridge_Response::with_public_cache( $response, self::SETTINGS_TTL );
    }

    // ── GET /navigation ──────────────────────────────────────────────────────

    public function get_navigation( WP_REST_Request $request ): WP_REST_Response {
        $cached = StoreFuse_Bridge_Cache::get( StoreFuse_Bridge_Cache::navigation_key() );

        if ( $cached !== null ) {
            $response = $this->success( $cached, 'storefuse.navigation.v1' );
            StoreFuse_Bridge_Response::mark_cache_hit( $response );
            return StoreFuse_Bridge_Response::with_public_cache( $response, self::NAV_TTL );
        }

        $data = $this->build_navigation();

        /**
         * Filter the navigation response.
         *
         * @param array           $data
         * @param WP_REST_Request $request
         */
        $data = apply_filters( 'storefuse_bridge_navigation_response', $data, $request );

        StoreFuse_Bridge_Cache::set( StoreFuse_Bridge_Cache::navigation_key(), $data, self::NAV_TTL );

        $response = $this->success( $data, 'storefuse.navigation.v1' );
        return StoreFuse_Bridge_Response::with_public_cache( $response, self::NAV_TTL );
    }

    // ── GET /homepage ────────────────────────────────────────────────────────

    public function get_homepage( WP_REST_Request $request ): WP_REST_Response {
        $cached = StoreFuse_Bridge_Cache::get( StoreFuse_Bridge_Cache::homepage_key() );

        if ( $cached !== null ) {
            $response = $this->success( $cached, 'storefuse.homepage.v1' );
            StoreFuse_Bridge_Response::mark_cache_hit( $response );
            return StoreFuse_Bridge_Response::with_public_cache( $response, self::HOMEPAGE_TTL );
        }

        $data = $this->build_homepage();

        /**
         * Filter the homepage response.
         * Can return a block array for the v2 Layout API format.
         *
         * @param array           $data
         * @param WP_REST_Request $request
         */
        $data = apply_filters( 'storefuse_bridge_homepage_response', $data, $request );

        StoreFuse_Bridge_Cache::set( StoreFuse_Bridge_Cache::homepage_key(), $data, self::HOMEPAGE_TTL );

        $response = $this->success( $data, 'storefuse.homepage.v1' );
        return StoreFuse_Bridge_Response::with_public_cache( $response, self::HOMEPAGE_TTL );
    }

    // ── Data builders ────────────────────────────────────────────────────────

    private function build_site_identity(): array {
        // Custom logo
        $logo_url     = null;
        $custom_logo  = get_theme_mod( 'custom_logo' );
        if ( $custom_logo ) {
            $logo_url = wp_get_attachment_image_url( $custom_logo, 'full' ) ?: null;
        }

        return [
            'name'        => get_bloginfo( 'name' ),
            'tagline'     => get_bloginfo( 'description' ),
            'url'         => get_site_url(),
            'logo_url'    => $logo_url,
            'favicon_url' => get_site_icon_url() ?: null,
            'admin_email' => get_bloginfo( 'admin_email' ),
        ];
    }

    private function build_store_config(): array {
        // Free shipping threshold - read from WC free shipping method settings
        $free_shipping_threshold = (float) StoreFuse_Bridge_Settings::get( 'free_shipping_threshold_amount', 0 );
        $free_shipping_label     = (string) StoreFuse_Bridge_Settings::get( 'free_shipping_threshold_label', '' );

        // COD enabled - check active payment gateways
        $cod_enabled = false;
        if ( WC()->payment_gateways() ) {
            $gateways    = WC()->payment_gateways()->get_available_payment_gateways();
            $cod_enabled = array_key_exists( 'cod', $gateways );
        }

        return [
            'currency'                  => get_woocommerce_currency(),
            'currency_symbol'           => get_woocommerce_currency_symbol(),
            'currency_position'         => get_option( 'woocommerce_currency_pos', 'left' ),
            'price_decimal_separator'   => wc_get_price_decimal_separator(),
            'price_thousand_separator'  => wc_get_price_thousand_separator(),
            'price_decimals'            => wc_get_price_decimals(),
            'free_shipping_threshold'   => $free_shipping_threshold,
            'free_shipping_label'       => $free_shipping_label,
            'return_policy_days'        => (int) StoreFuse_Bridge_Settings::get( 'return_policy_days', 7 ),
            'cod_enabled'               => $cod_enabled,
        ];
    }

    private function build_header_config(): array {
        return [
            'announcement_bar_enabled'  => (bool) StoreFuse_Bridge_Settings::get( 'announcement_bar_enabled', false ),
            'announcement_bar_text'     => (string) StoreFuse_Bridge_Settings::get( 'announcement_bar_text', '' ),
            'announcement_bar_bg_color' => (string) StoreFuse_Bridge_Settings::get( 'announcement_bar_bg_color', '#E85D04' ),
            'announcement_bar_link'     => (string) StoreFuse_Bridge_Settings::get( 'announcement_bar_link', '' ),
        ];
    }

    private function build_trust_badges(): array {
        $raw = StoreFuse_Bridge_Settings::get( 'trust_badges', [] );
        if ( is_string( $raw ) ) {
            $raw = json_decode( $raw, true ) ?? [];
        }
        return is_array( $raw ) ? $raw : [];
    }

    private function build_social_links(): array {
        return [
            'instagram' => StoreFuse_Bridge_Settings::get( 'social_instagram' ) ?: null,
            'facebook'  => StoreFuse_Bridge_Settings::get( 'social_facebook' )  ?: null,
            'twitter'   => StoreFuse_Bridge_Settings::get( 'social_twitter' )   ?: null,
            'youtube'   => StoreFuse_Bridge_Settings::get( 'social_youtube' )   ?: null,
            'pinterest' => StoreFuse_Bridge_Settings::get( 'social_pinterest' ) ?: null,
            'whatsapp'  => StoreFuse_Bridge_Settings::get( 'social_whatsapp' )  ?: null,
        ];
    }

    private function build_navigation(): array {
        return [
            'main'       => $this->get_menu_items( 'storefuse-header' ),
            'footer'     => $this->get_menu_items( 'storefuse-footer' ),
            'categories' => $this->get_top_categories(),
        ];
    }

    /**
     * Read items from a registered WordPress nav menu location.
     * Falls back to an empty array if no menu is assigned.
     *
     * @param string $location  Theme menu location slug
     * @return array
     */
    private function get_menu_items( string $location ): array {
        $locations = get_nav_menu_locations();
        if ( empty( $locations[ $location ] ) ) {
            return [];
        }

        $menu  = wp_get_nav_menu_object( $locations[ $location ] );
        if ( ! $menu ) {
            return [];
        }

        $items = wp_get_nav_menu_items( $menu->term_id );
        if ( ! is_array( $items ) ) {
            return [];
        }

        $result = [];
        foreach ( $items as $item ) {
            $result[] = [
                'id'     => (int) $item->ID,
                'label'  => $item->title,
                'href'   => $this->make_relative_url( $item->url ),
                'target' => $item->target ?: '_self',
                'parent' => $item->menu_item_parent ? (int) $item->menu_item_parent : null,
            ];
        }
        return $result;
    }

    /**
     * Return the top-level product categories for the category navigation bar.
     */
    private function get_top_categories(): array {
        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => 0,
            'number'     => 12,
            'orderby'    => 'menu_order',
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return [];
        }

        $result = [];
        foreach ( $terms as $term ) {
            // Skip the WooCommerce built-in "Uncategorized" term
            if ( $term->slug === 'uncategorized' ) {
                continue;
            }

            $thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
            $result[]     = [
                'id'        => (string) $term->term_id,
                'label'     => $term->name,
                'slug'      => $term->slug,
                'href'      => '/category/' . $term->slug,
                'image_url' => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : null,
                'icon'      => (string) get_term_meta( $term->term_id, 'storefuse_icon', true ),
            ];
        }
        return $result;
    }

    private function build_homepage(): array {
        $s = StoreFuse_Bridge_Settings::all();

        return [
            'announcement_bar' => [
                'enabled'  => (bool) ( $s['announcement_bar_enabled'] ?? false ),
                'text'     => (string) ( $s['announcement_bar_text'] ?? '' ),
                'bg_color' => (string) ( $s['announcement_bar_bg_color'] ?? '#E85D04' ),
                'link'     => (string) ( $s['announcement_bar_link'] ?? '' ),
            ],
            'hero' => [
                'badge_text'          => (string) ( $s['hero_badge_text'] ?? '' ),
                'headline'            => (string) ( $s['hero_headline'] ?? get_bloginfo( 'name' ) ),
                'headline_highlight'  => (string) ( $s['hero_headline_highlight'] ?? '' ),
                'subheadline'         => (string) ( $s['hero_subheadline'] ?? get_bloginfo( 'description' ) ),
                'cta_primary_label'   => (string) ( $s['hero_cta_primary_label'] ?? 'Shop Now' ),
                'cta_primary_href'    => (string) ( $s['hero_cta_primary_href'] ?? '/shop' ),
                'cta_secondary_label' => (string) ( $s['hero_cta_secondary_label'] ?? '' ),
                'cta_secondary_href'  => (string) ( $s['hero_cta_secondary_href'] ?? '' ),
                'image'               => $this->build_hero_image(),
                'rating_text'         => (string) ( $s['hero_rating_text'] ?? '' ),
                'shipping_text'       => (string) ( $s['hero_shipping_text'] ?? '' ),
            ],
            'featured_categories' => $this->build_featured_categories(),
            'trust_items'         => $this->build_trust_badges(),
        ];
    }

    private function build_hero_image(): ?array {
        $attachment_id = (int) StoreFuse_Bridge_Settings::get( 'hero_image_id', 0 );
        if ( ! $attachment_id ) {
            return null;
        }
        return StoreFuse_Bridge_Format::image( $attachment_id );
    }

    private function build_featured_categories(): array {
        $raw = StoreFuse_Bridge_Settings::get( 'featured_categories', [] );
        if ( is_string( $raw ) ) {
            $raw = json_decode( $raw, true ) ?? [];
        }
        if ( ! is_array( $raw ) || empty( $raw ) ) {
            // Fall back to top 6 categories
            return array_slice( $this->get_top_categories(), 0, 6 );
        }
        return $raw;
    }

    // ── URL helpers ──────────────────────────────────────────────────────────

    /**
     * Convert an absolute site URL to a relative path.
     * External URLs are returned unchanged.
     */
    private function make_relative_url( string $url ): string {
        $site_url = get_site_url();
        if ( strpos( $url, $site_url ) === 0 ) {
            return '/' . ltrim( substr( $url, strlen( $site_url ) ), '/' );
        }
        return $url;
    }
}
