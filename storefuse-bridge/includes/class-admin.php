<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin menu and settings page registration.
 */
class StoreFuse_Bridge_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_init',            [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_notices',         [ $this, 'dismiss_setup_notice_on_dashboard' ] );
        add_filter( 'plugin_action_links_' . STOREFUSE_BRIDGE_BASENAME, [ $this, 'plugin_action_links' ] );

        add_action( 'wp_ajax_storefuse_bridge_flush_cache',       [ $this, 'ajax_flush_cache' ] );
        add_action( 'wp_ajax_storefuse_bridge_flush_cache_group', [ $this, 'ajax_flush_cache_group' ] );
        add_action( 'wp_ajax_storefuse_bridge_clear_webhook_log', [ $this, 'ajax_clear_webhook_log' ] );
        add_action( 'wp_ajax_storefuse_bridge_test_webhook',      [ $this, 'ajax_test_webhook' ] );
        add_action( 'wp_ajax_storefuse_bridge_dismiss_setup',     [ $this, 'ajax_dismiss_setup_notice' ] );
    }

    /**
     * Add Settings link on the Plugins list (WooCommerce-style).
     *
     * @param string[] $links Existing action links.
     * @return string[]
     */
    public function plugin_action_links( array $links ): array {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return $links;
        }

        $settings = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=storefuse-bridge' ) ),
            esc_html__( 'Settings', 'storefuse-bridge' )
        );

        array_unshift( $links, $settings );

        return $links;
    }

    /**
     * Canonical documentation URL (GitHub docs index).
     *
     * Override with filter `storefuse_bridge_documentation_url` or constant STOREFUSE_BRIDGE_DOCS_URL.
     */
    public static function documentation_url(): string {
        $default = defined( 'STOREFUSE_BRIDGE_DOCS_URL' )
            ? STOREFUSE_BRIDGE_DOCS_URL
            : 'https://github.com/ProgrammerNomad/storefuse-bridge/blob/main/docs/README.md';

        return (string) apply_filters( 'storefuse_bridge_documentation_url', $default );
    }

    public function register_menus(): void {
        add_menu_page(
            __( 'StoreFuse Bridge', 'storefuse-bridge' ),
            __( 'StoreFuse', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge',
            [ $this, 'page_dashboard' ],
            'dashicons-rest-api',
            56
        );

        add_submenu_page(
            'storefuse-bridge',
            __( 'StoreFuse Bridge - Dashboard', 'storefuse-bridge' ),
            __( 'Dashboard', 'storefuse-bridge' ),
            'manage_woocommerce',
            'storefuse-bridge',
            [ $this, 'page_dashboard' ]
        );

        $pages = [
            'storefuse-bridge-general'     => [ __( 'General', 'storefuse-bridge' ), 'page_general' ],
            'storefuse-bridge-homepage'    => [ __( 'Homepage', 'storefuse-bridge' ), 'page_homepage' ],
            'storefuse-bridge-navigation'  => [ __( 'Navigation', 'storefuse-bridge' ), 'page_navigation' ],
            'storefuse-bridge-social'      => [ __( 'Social & Trust', 'storefuse-bridge' ), 'page_social' ],
            'storefuse-bridge-checkout'    => [ __( 'Checkout', 'storefuse-bridge' ), 'page_checkout' ],
            'storefuse-bridge-storefront'  => [ __( 'Storefront & Clients', 'storefuse-bridge' ), 'page_storefront' ],
            'storefuse-bridge-api'         => [ __( 'API & Tools', 'storefuse-bridge' ), 'page_api' ],
            'storefuse-bridge-advanced'    => [ __( 'Advanced', 'storefuse-bridge' ), 'page_advanced' ],
            'storefuse-bridge-extensions'  => [ __( 'Extensions', 'storefuse-bridge' ), 'page_extensions' ],
        ];

        foreach ( $pages as $slug => [ $title, $callback ] ) {
            add_submenu_page(
                'storefuse-bridge',
                'StoreFuse - ' . $title,
                $title,
                'manage_woocommerce',
                $slug,
                [ $this, $callback ]
            );
        }
    }

    public function register_settings(): void {
        register_setting(
            'storefuse_bridge_settings_group',
            'storefuse_bridge_settings',
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );
    }

    public function sanitize_settings( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return StoreFuse_Bridge_Settings::all();
        }

        $existing = StoreFuse_Bridge_Settings::all();
        $patch    = [];

        if ( array_key_exists( 'announcement_bar_enabled', $input ) ) {
            $patch['announcement_bar_enabled'] = ! empty( $input['announcement_bar_enabled'] );
        }
        if ( array_key_exists( 'announcement_bar_text', $input ) ) {
            $patch['announcement_bar_text'] = sanitize_text_field( $input['announcement_bar_text'] );
        }
        if ( array_key_exists( 'announcement_bar_bg_color', $input ) ) {
            $patch['announcement_bar_bg_color'] = sanitize_hex_color( $input['announcement_bar_bg_color'] ) ?: '#E85D04';
        }
        if ( array_key_exists( 'announcement_bar_link', $input ) ) {
            $patch['announcement_bar_link'] = esc_url_raw( $input['announcement_bar_link'] );
        }

        if ( array_key_exists( 'return_policy_days', $input ) ) {
            $patch['return_policy_days'] = absint( $input['return_policy_days'] );
        }
        if ( array_key_exists( 'free_shipping_threshold_label', $input ) ) {
            $patch['free_shipping_threshold_label'] = sanitize_text_field( $input['free_shipping_threshold_label'] );
        }
        if ( array_key_exists( 'free_shipping_threshold_amount', $input ) ) {
            $patch['free_shipping_threshold_amount'] = (float) $input['free_shipping_threshold_amount'];
        }

        foreach (
            [
                'hero_badge_text',
                'hero_headline',
                'hero_headline_highlight',
                'hero_subheadline',
                'hero_cta_primary_label',
                'hero_rating_text',
                'hero_shipping_text',
            ] as $key
        ) {
            if ( array_key_exists( $key, $input ) ) {
                $patch[ $key ] = sanitize_text_field( $input[ $key ] );
            }
        }
        if ( array_key_exists( 'hero_cta_primary_href', $input ) ) {
            $patch['hero_cta_primary_href'] = esc_url_raw( $input['hero_cta_primary_href'] );
        }
        if ( array_key_exists( 'hero_cta_secondary_label', $input ) ) {
            $patch['hero_cta_secondary_label'] = sanitize_text_field( $input['hero_cta_secondary_label'] );
        }
        if ( array_key_exists( 'hero_cta_secondary_href', $input ) ) {
            $patch['hero_cta_secondary_href'] = esc_url_raw( $input['hero_cta_secondary_href'] );
        }
        if ( array_key_exists( 'hero_image_id', $input ) ) {
            $patch['hero_image_id'] = absint( $input['hero_image_id'] );
        }

        foreach ( [ 'instagram', 'facebook', 'twitter', 'youtube', 'pinterest' ] as $platform ) {
            $key = "social_{$platform}";
            if ( array_key_exists( $key, $input ) ) {
                $patch[ $key ] = esc_url_raw( $input[ $key ] );
            }
        }
        if ( array_key_exists( 'social_whatsapp', $input ) ) {
            $patch['social_whatsapp'] = sanitize_text_field( $input['social_whatsapp'] );
        }

        if ( array_key_exists( 'trust_badges', $input ) ) {
            $patch['trust_badges'] = wp_json_encode(
                StoreFuse_Bridge_Settings_Sanitizer::parse_trust_badges( $input['trust_badges'] )
            );
        }
        if ( array_key_exists( 'featured_categories', $input ) ) {
            $patch['featured_categories'] = wp_json_encode(
                StoreFuse_Bridge_Settings_Sanitizer::parse_featured_categories( $input['featured_categories'] )
            );
        }

        if ( ! empty( $input['_sfb_save_modules'] ) ) {
            foreach ( StoreFuse_Bridge_Module_Status::toggleable_module_ids() as $mod ) {
                $patch[ "module_{$mod}_enabled" ] = ! empty( $input[ "module_{$mod}_enabled" ] );
            }
        }

        if ( array_key_exists( 'checkout_mode', $input ) ) {
            $valid_modes              = [ 'redirect', 'headless' ];
            $patch['checkout_mode'] = in_array( $input['checkout_mode'], $valid_modes, true )
                ? $input['checkout_mode']
                : 'redirect';
        }
        if ( array_key_exists( 'checkout_redirect_label', $input ) ) {
            $patch['checkout_redirect_label'] = sanitize_text_field( $input['checkout_redirect_label'] );
        }
        if ( array_key_exists( 'checkout_page_url', $input ) ) {
            $patch['checkout_page_url'] = esc_url_raw( $input['checkout_page_url'] );
        }

        if ( array_key_exists( 'storefront_url', $input ) ) {
            $patch['storefront_url'] = esc_url_raw( $input['storefront_url'] );
        }
        if ( array_key_exists( 'storefront_reset_path', $input ) ) {
            $reset_path                       = sanitize_text_field( $input['storefront_reset_path'] );
            $patch['storefront_reset_path'] = '/' . ltrim( $reset_path, '/' );
        }
        if ( array_key_exists( 'storefront_revalidate_path', $input ) ) {
            $revalidate_path                       = sanitize_text_field( $input['storefront_revalidate_path'] );
            $patch['storefront_revalidate_path'] = '/' . ltrim( $revalidate_path, '/' );
        }
        if ( array_key_exists( 'revalidation_secret', $input ) ) {
            $patch['revalidation_secret'] = sanitize_text_field( $input['revalidation_secret'] );
        }
        if ( array_key_exists( 'primary_client', $input ) ) {
            $valid_clients             = [ 'nextjs', 'flutter', 'other' ];
            $patch['primary_client'] = in_array( $input['primary_client'], $valid_clients, true )
                ? $input['primary_client']
                : 'other';
        }

        if ( array_key_exists( 'cors_enabled', $input ) ) {
            $patch['cors_enabled'] = ! empty( $input['cors_enabled'] );
        }
        if ( array_key_exists( 'cors_allowed_origins', $input ) ) {
            $patch['cors_allowed_origins'] = sanitize_textarea_field( $input['cors_allowed_origins'] );
        }

        if ( array_key_exists( 'homepage_best_sellers_heading', $input ) ) {
            $patch['homepage_best_sellers_heading'] = sanitize_text_field( $input['homepage_best_sellers_heading'] );
        }
        if ( array_key_exists( 'homepage_best_sellers_source', $input ) ) {
            $valid_sources                         = [ 'best-selling', 'featured', 'manual' ];
            $patch['homepage_best_sellers_source'] = in_array( $input['homepage_best_sellers_source'], $valid_sources, true )
                ? $input['homepage_best_sellers_source']
                : 'best-selling';
        }
        if ( array_key_exists( 'homepage_best_sellers_count', $input ) ) {
            $patch['homepage_best_sellers_count'] = min( 24, max( 1, absint( $input['homepage_best_sellers_count'] ) ) );
        }
        if ( array_key_exists( 'homepage_best_sellers_ids', $input ) ) {
            $patch['homepage_best_sellers_ids'] = sanitize_text_field( $input['homepage_best_sellers_ids'] );
        }

        if ( array_key_exists( 'homepage_new_arrivals_heading', $input ) ) {
            $patch['homepage_new_arrivals_heading'] = sanitize_text_field( $input['homepage_new_arrivals_heading'] );
        }
        if ( array_key_exists( 'homepage_new_arrivals_count', $input ) ) {
            $patch['homepage_new_arrivals_count'] = min( 24, max( 1, absint( $input['homepage_new_arrivals_count'] ) ) );
        }
        if ( array_key_exists( 'homepage_new_arrivals_category', $input ) ) {
            $patch['homepage_new_arrivals_category'] = sanitize_title( $input['homepage_new_arrivals_category'] );
        }

        if ( array_key_exists( 'homepage_promo_banner_enabled', $input ) ) {
            $patch['homepage_promo_banner_enabled'] = ! empty( $input['homepage_promo_banner_enabled'] );
        }
        if ( array_key_exists( 'homepage_promo_banner_headline', $input ) ) {
            $patch['homepage_promo_banner_headline'] = sanitize_text_field( $input['homepage_promo_banner_headline'] );
        }
        if ( array_key_exists( 'homepage_promo_banner_body', $input ) ) {
            $patch['homepage_promo_banner_body'] = sanitize_textarea_field( $input['homepage_promo_banner_body'] );
        }
        if ( array_key_exists( 'homepage_promo_banner_cta_label', $input ) ) {
            $patch['homepage_promo_banner_cta_label'] = sanitize_text_field( $input['homepage_promo_banner_cta_label'] );
        }
        if ( array_key_exists( 'homepage_promo_banner_cta_href', $input ) ) {
            $patch['homepage_promo_banner_cta_href'] = esc_url_raw( $input['homepage_promo_banner_cta_href'] );
        }
        if ( array_key_exists( 'homepage_promo_banner_bg_color', $input ) ) {
            $patch['homepage_promo_banner_bg_color'] = sanitize_hex_color( $input['homepage_promo_banner_bg_color'] ) ?: '#1e293b';
        }

        $clean = array_merge( $existing, $patch );

        StoreFuse_Bridge_Cache::flush_all();
        do_action( 'storefuse_bridge_settings_updated' );

        add_settings_error(
            'storefuse_bridge_settings',
            'settings_updated',
            __( 'Settings saved.', 'storefuse-bridge' ),
            'success'
        );

        return $clean;
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'storefuse-bridge' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.css',
            [],
            STOREFUSE_BRIDGE_VERSION
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.js',
            [ 'jquery', 'wp-color-picker', 'media-upload' ],
            STOREFUSE_BRIDGE_VERSION,
            true
        );

        wp_enqueue_style( 'wp-color-picker' );

        wp_localize_script( 'storefuse-bridge-admin', 'sfbAdmin', [
            'nonce'    => wp_create_nonce( 'sfb_admin_nonce' ),
            'flushUrl' => admin_url( 'admin-ajax.php' ),
            'apiBase'  => get_site_url() . '/wp-json/storefuse/v1',
            'i18n'     => [
                'flushing'      => __( 'Flushing…', 'storefuse-bridge' ),
                'flushDone'     => __( 'Cache flushed.', 'storefuse-bridge' ),
                'flushError'    => __( 'Error flushing cache.', 'storefuse-bridge' ),
                'testing'       => __( 'Testing…', 'storefuse-bridge' ),
                'testOk'        => __( 'Webhook responded successfully.', 'storefuse-bridge' ),
                'testFail'      => __( 'Webhook test failed.', 'storefuse-bridge' ),
                'confirmFlush'  => __( 'Flush all StoreFuse Bridge cache?', 'storefuse-bridge' ),
            ],
        ] );
    }

    public function dismiss_setup_notice_on_dashboard(): void {
        if ( ! is_admin() || ! isset( $_GET['page'] ) || $_GET['page'] !== 'storefuse-bridge' ) {
            return;
        }
        delete_transient( 'storefuse_bridge_show_setup_notice' );
    }

    public function ajax_flush_cache(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        StoreFuse_Bridge_Cache::flush_all();
        wp_send_json_success( [ 'message' => __( 'All cache flushed.', 'storefuse-bridge' ) ] );
    }

    public function ajax_flush_cache_group(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        $group = sanitize_key( $_POST['group'] ?? 'all' );
        StoreFuse_Bridge_Cache::flush_group( $group );

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %s cache group name */
                __( '%s cache flushed.', 'storefuse-bridge' ),
                ucfirst( $group )
            ),
        ] );
    }

    public function ajax_clear_webhook_log(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        delete_option( 'storefuse_bridge_webhook_log' );
        wp_send_json_success( [ 'message' => 'Webhook log cleared.' ] );
    }

    public function ajax_test_webhook(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        $module = new StoreFuse_Bridge_Module_Webhooks();
        $result = $module->test_connection();

        if ( $result['ok'] ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    public function ajax_dismiss_setup_notice(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );
        if ( current_user_can( 'manage_woocommerce' ) ) {
            delete_transient( 'storefuse_bridge_show_setup_notice' );
        }
        wp_send_json_success();
    }

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

    public function page_navigation(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-navigation.php';
    }

    public function page_checkout(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-checkout.php';
    }

    public function page_storefront(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-storefront.php';
    }

    public function page_api(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-api.php';
    }

    public function page_advanced(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-advanced.php';
    }

    public function page_extensions(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-extensions.php';
    }

    /**
     * Human labels for module IDs (dashboard / advanced).
     *
     * @return array<string, string>
     */
    public static function module_labels(): array {
        return [
            'status'     => __( 'Status (health)', 'storefuse-bridge' ),
            'auth'       => __( 'Authentication', 'storefuse-bridge' ),
            'settings'   => __( 'Settings / Navigation / Homepage', 'storefuse-bridge' ),
            'products'   => __( 'Products', 'storefuse-bridge' ),
            'categories' => __( 'Categories', 'storefuse-bridge' ),
            'search'     => __( 'Search', 'storefuse-bridge' ),
            'attributes' => __( 'Attributes', 'storefuse-bridge' ),
            'tags'       => __( 'Tags', 'storefuse-bridge' ),
            'cart'       => __( 'Cart', 'storefuse-bridge' ),
            'checkout'   => __( 'Checkout', 'storefuse-bridge' ),
            'account'    => __( 'Account', 'storefuse-bridge' ),
            'orders'     => __( 'Orders', 'storefuse-bridge' ),
            'addresses'  => __( 'Addresses', 'storefuse-bridge' ),
            'wishlist'   => __( 'Wishlist', 'storefuse-bridge' ),
            'reviews'    => __( 'Reviews', 'storefuse-bridge' ),
            'posts'      => __( 'Blog posts', 'storefuse-bridge' ),
            'utils'      => __( 'Utilities', 'storefuse-bridge' ),
            'downloads'  => __( 'Downloads', 'storefuse-bridge' ),
            'webhooks'   => __( 'ISR Webhooks', 'storefuse-bridge' ),
        ];
    }

    /**
     * Routes affected when a toggleable module is disabled (documentation map).
     *
     * @return array<string, list<string>>
     */
    public static function module_route_map(): array {
        return [
            'settings'   => [ 'GET /settings', 'GET /navigation', 'GET /homepage' ],
            'products'   => [ 'GET /products', 'GET /products/{slug}', 'POST /products/{slug}/notify' ],
            'categories' => [ 'GET /categories', 'GET /categories/{slug}' ],
            'search'     => [ 'GET /search' ],
            'attributes' => [ 'GET /attributes' ],
            'tags'       => [ 'GET /tags' ],
            'cart'       => [ 'GET /cart', 'POST /cart/add', 'POST /cart/update', 'POST /cart/remove', 'POST /cart/coupon' ],
            'checkout'   => [ 'GET /checkout/config', 'POST /checkout', 'POST /checkout/redirect-url', 'GET /checkout/payment-methods', 'GET /checkout/shipping-methods' ],
            'account'    => [ 'GET /account', 'POST /account/change-password' ],
            'orders'     => [ 'GET /orders', 'GET /orders/{id}', 'POST /orders/{id}/cancel', 'POST /orders/{id}/reorder' ],
            'addresses'  => [ 'GET /addresses', 'PUT /addresses/billing', 'PUT /addresses/shipping' ],
            'wishlist'   => [ 'GET /wishlist', 'POST /wishlist/add', 'POST /wishlist/remove' ],
            'reviews'    => [ 'GET /reviews', 'POST /reviews' ],
            'posts'      => [ 'GET /posts', 'GET /posts/{slug}' ],
            'utils'      => [ 'GET /utils/countries', 'GET /utils/pincode/{pincode}' ],
            'downloads'  => [ 'GET /downloads' ],
            'webhooks'   => [ '(outgoing) POST {storefront}/api/revalidate' ],
        ];
    }

    /**
     * Module IDs that show a storefront impact warning when disabled on Advanced.
     *
     * @return list<string>
     */
    public static function module_disable_warn_ids(): array {
        return [ 'settings', 'cart', 'checkout', 'account' ];
    }

    /**
     * Build a nested menu preview from flat nav items.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function nest_nav_items( array $items ): array {
        $by_id = [];
        foreach ( $items as $item ) {
            $item['children'] = [];
            $by_id[ $item['id'] ] = $item;
        }
        $tree = [];
        foreach ( $by_id as $id => $item ) {
            $parent = $item['parent'] ?? null;
            if ( $parent && isset( $by_id[ $parent ] ) ) {
                $by_id[ $parent ]['children'][] = &$by_id[ $id ];
            } else {
                $tree[] = &$by_id[ $id ];
            }
        }
        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_menu_items_for_location( string $location ): array {
        $locations = get_nav_menu_locations();
        if ( empty( $locations[ $location ] ) ) {
            return [];
        }
        $menu = wp_get_nav_menu_object( $locations[ $location ] );
        if ( ! $menu ) {
            return [];
        }
        $items = wp_get_nav_menu_items( $menu->term_id );
        if ( ! is_array( $items ) ) {
            return [];
        }
        $site_url = get_site_url();
        $result   = [];
        foreach ( $items as $item ) {
            $href = $item->url;
            if ( strpos( $href, $site_url ) === 0 ) {
                $href = '/' . ltrim( substr( $href, strlen( $site_url ) ), '/' );
            }
            $result[] = [
                'id'     => (int) $item->ID,
                'label'  => $item->title,
                'href'   => $href,
                'parent' => $item->menu_item_parent ? (int) $item->menu_item_parent : null,
            ];
        }
        return $result;
    }

    public static function render_nav_preview_list( array $nodes, int $depth = 0 ): void {
        if ( empty( $nodes ) ) {
            echo '<em>' . esc_html__( 'No items', 'storefuse-bridge' ) . '</em>';
            return;
        }
        echo '<ul class="sfb-nav-preview" style="margin-left:' . esc_attr( (string) ( $depth * 16 ) ) . 'px;">';
        foreach ( $nodes as $node ) {
            echo '<li><code>' . esc_html( $node['href'] ?? '' ) . '</code> - ' . esc_html( $node['label'] ?? '' );
            if ( ! empty( $node['children'] ) ) {
                self::render_nav_preview_list( $node['children'], $depth + 1 );
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
