<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin menu and settings page registration.
 *
 * Registers the StoreFuse Bridge menu under WooCommerce → StoreFuse Bridge.
 * Settings are saved via standard WordPress options API (wp_options).
 */
class StoreFuse_Bridge_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_init',            [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_storefuse_bridge_flush_cache', [ $this, 'ajax_flush_cache' ] );
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function register_menus(): void {
        // Top-level menu item
        add_menu_page(
            __( 'StoreFuse Bridge', 'storefuse-bridge' ),
            __( 'StoreFuse', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge',
            [ $this, 'page_dashboard' ],
            'dashicons-rest-api',
            56
        );

        // Dashboard (rename the auto-created duplicate submenu entry)
        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse Bridge - Dashboard', 'storefuse-bridge' ),
            __( 'Dashboard', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge',
            [ $this, 'page_dashboard' ]
        );

        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse - General Settings', 'storefuse-bridge' ),
            __( 'General', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge-general',
            [ $this, 'page_general' ]
        );

        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse - Homepage', 'storefuse-bridge' ),
            __( 'Homepage', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge-homepage',
            [ $this, 'page_homepage' ]
        );

        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse - Social & Trust', 'storefuse-bridge' ),
            __( 'Social & Trust', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge-social',
            [ $this, 'page_social' ]
        );

        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse - Advanced', 'storefuse-bridge' ),
            __( 'Advanced', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge-advanced',
            [ $this, 'page_advanced' ]
        );
    }

    // ── Settings API registration ─────────────────────────────────────────────

    public function register_settings(): void {
        register_setting(
            'storefuse_bridge_settings_group',
            'storefuse_bridge_settings',
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );
    }

    public function sanitize_settings( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $clean = [];

        // Announcement bar
        $clean['announcement_bar_enabled']  = ! empty( $input['announcement_bar_enabled'] );
        $clean['announcement_bar_text']     = sanitize_text_field( $input['announcement_bar_text'] ?? '' );
        $clean['announcement_bar_bg_color'] = sanitize_hex_color( $input['announcement_bar_bg_color'] ?? '#E85D04' ) ?: '#E85D04';
        $clean['announcement_bar_link']     = esc_url_raw( $input['announcement_bar_link'] ?? '' );

        // Store policies
        $clean['return_policy_days']             = absint( $input['return_policy_days'] ?? 7 );
        $clean['free_shipping_threshold_label']  = sanitize_text_field( $input['free_shipping_threshold_label'] ?? '' );
        $clean['free_shipping_threshold_amount'] = (float) ( $input['free_shipping_threshold_amount'] ?? 0 );

        // Hero
        $clean['hero_badge_text']          = sanitize_text_field( $input['hero_badge_text'] ?? '' );
        $clean['hero_headline']            = sanitize_text_field( $input['hero_headline'] ?? '' );
        $clean['hero_headline_highlight']  = sanitize_text_field( $input['hero_headline_highlight'] ?? '' );
        $clean['hero_subheadline']         = sanitize_text_field( $input['hero_subheadline'] ?? '' );
        $clean['hero_cta_primary_label']   = sanitize_text_field( $input['hero_cta_primary_label'] ?? 'Shop Now' );
        $clean['hero_cta_primary_href']    = esc_url_raw( $input['hero_cta_primary_href'] ?? '/shop' );
        $clean['hero_cta_secondary_label'] = sanitize_text_field( $input['hero_cta_secondary_label'] ?? '' );
        $clean['hero_cta_secondary_href']  = esc_url_raw( $input['hero_cta_secondary_href'] ?? '' );
        $clean['hero_image_id']            = absint( $input['hero_image_id'] ?? 0 );
        $clean['hero_rating_text']         = sanitize_text_field( $input['hero_rating_text'] ?? '' );
        $clean['hero_shipping_text']       = sanitize_text_field( $input['hero_shipping_text'] ?? '' );

        // Social links
        foreach ( [ 'instagram', 'facebook', 'twitter', 'youtube', 'pinterest', 'whatsapp' ] as $platform ) {
            $clean[ "social_{$platform}" ] = esc_url_raw( $input[ "social_{$platform}" ] ?? '' );
        }

        // Trust badges (stored as JSON string)
        $clean['trust_badges'] = isset( $input['trust_badges'] ) ? $input['trust_badges'] : '[]';

        // Module toggles
        foreach ( [ 'products', 'categories', 'search', 'cart', 'checkout', 'content', 'webhooks' ] as $mod ) {
            $clean[ "module_{$mod}_enabled" ] = ! empty( $input[ "module_{$mod}_enabled" ] );
        }

        // Flush cache whenever settings are saved
        StoreFuse_Bridge_Cache::flush_all();

        return $clean;
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function enqueue_assets( string $hook ): void {
        // Only load on our admin pages
        if ( strpos( $hook, 'storefuse-bridge' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.css',
            [],
            STOREFUSE_BRIDGE_VERSION
        );

        wp_enqueue_media(); // For the hero image media picker

        wp_enqueue_script(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.js',
            [ 'jquery', 'wp-color-picker', 'media-upload' ],
            STOREFUSE_BRIDGE_VERSION,
            true
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_localize_script( 'storefuse-bridge-admin', 'sfbAdmin', [
            'nonce'       => wp_create_nonce( 'sfb_admin_nonce' ),
            'flushUrl'    => admin_url( 'admin-ajax.php' ),
            'apiBase'     => get_site_url() . '/wp-json/storefuse/v1',
        ] );
    }

    // ── AJAX: flush cache ─────────────────────────────────────────────────────

    public function ajax_flush_cache(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        StoreFuse_Bridge_Cache::flush_all();
        wp_send_json_success( [ 'message' => 'Cache flushed successfully.' ] );
    }

    // ── Page templates ────────────────────────────────────────────────────────

    public function page_dashboard(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-dashboard.php';
    }

    public function page_general(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-general.php';
    }

    public function page_homepage(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-homepage.php';
    }

    public function page_social(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-social.php';
    }

    public function page_advanced(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-advanced.php';
    }
}
