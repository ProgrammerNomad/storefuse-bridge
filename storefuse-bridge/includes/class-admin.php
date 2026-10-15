<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin menu and settings page registration.
 */
class StoreFuse_Bridge_Admin {

    /**
     * Capability required to view and save Bridge settings (WooCommerce shop managers).
     */
    public static function settings_capability(): string {
        return (string) apply_filters( 'storefuse_bridge_settings_capability', 'manage_woocommerce' );
    }

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_menu',            [ $this, 'redirect_legacy_pages' ], 0 );
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
        if ( ! current_user_can( self::settings_capability() ) ) {
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
        $cap = self::settings_capability();
        // WordPress recolors a base64 SVG for the user's admin color scheme.
        $menu_icon = 'data:image/svg+xml;base64,' . base64_encode(
            file_get_contents( STOREFUSE_BRIDGE_PATH . 'assets/storefuse-admin-mark.svg' )
        );
        add_menu_page(
            __( 'StoreFuse Bridge', 'storefuse-bridge' ),
            __( 'StoreFuse', 'storefuse-bridge' ),
            $cap,
            'storefuse-bridge',
            [ $this, 'page_dashboard' ],
            $menu_icon,
            56
        );

        $client = (string) StoreFuse_Bridge_Settings::get( 'primary_client', 'other' );
        $pages  = [
            'storefuse-bridge'               => [ __( 'Dashboard', 'storefuse-bridge' ), 'page_dashboard', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-setup'          => [ __( 'Setup', 'storefuse-bridge' ), 'page_setup', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-homepage'       => [ __( 'Homepage', 'storefuse-bridge' ), 'page_homepage', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-social'         => [ __( 'Social & Trust', 'storefuse-bridge' ), 'page_social', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-branding'       => [ __( 'Branding', 'storefuse-bridge' ), 'page_branding', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-app-layout'     => [ __( 'App Layout', 'storefuse-bridge' ), 'page_app_layout', [ 'flutter' ] ],
            'storefuse-bridge-navigation'     => [ __( 'Navigation', 'storefuse-bridge' ), 'page_navigation', [ 'nextjs', 'other' ] ],
            'storefuse-bridge-checkout'       => [ __( 'Checkout', 'storefuse-bridge' ), 'page_checkout', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-policies'       => [ __( 'Store Policies', 'storefuse-bridge' ), 'page_store_policies', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-api'            => [ __( 'API & CORS', 'storefuse-bridge' ), 'page_api', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-webhooks'       => [ __( 'Webhooks (ISR)', 'storefuse-bridge' ), 'page_webhooks', [ 'nextjs', 'other' ] ],
            'storefuse-bridge-modules'        => [ __( 'Modules', 'storefuse-bridge' ), 'page_modules', [ 'flutter', 'nextjs', 'other' ] ],
            'storefuse-bridge-extensions'     => [ __( 'Extensions', 'storefuse-bridge' ), 'page_extensions', [ 'flutter', 'nextjs', 'other' ] ],
        ];

        foreach ( $pages as $slug => [ $title, $callback, $clients ] ) {
            $parent_slug = in_array( $client, $clients, true ) ? 'storefuse-bridge' : '';
            add_submenu_page(
                $parent_slug,
                'StoreFuse - ' . $title,
                $title,
                $cap,
                $slug,
                [ $this, $callback ]
            );
        }
    }

    /**
     * Redirect bookmarks for the four menu slugs replaced by the v2 screens.
     */
    public function redirect_legacy_pages(): void {
        if ( ! is_admin() || ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) {
            return;
        }

        $legacy_pages = [
            'storefuse-bridge-general'    => 'storefuse-bridge-branding',
            'storefuse-bridge-storefront' => 'storefuse-bridge-setup',
            'storefuse-bridge-advanced'   => 'storefuse-bridge-modules',
            'storefuse-bridge-mobile-app' => 'storefuse-bridge-app-layout',
        ];
        $page         = sanitize_key( wp_unslash( $_GET['page'] ) );

        if ( ! isset( $legacy_pages[ $page ] ) ) {
            return;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=' . $legacy_pages[ $page ] ), 302, 'StoreFuse Bridge' );
        exit;
    }

    /**
     * Render an informational notice when a client-specific page is hidden in the sidebar.
     * The WordPress menu capability remains the sole access check.
     */
    public static function render_client_context_notice( string $page_slug ): void {
        $client = (string) StoreFuse_Bridge_Settings::get( 'primary_client', 'other' );
        $hidden_for = [
            'storefuse-bridge-app-layout' => [ 'nextjs', 'other' ],
            'storefuse-bridge-navigation' => [ 'flutter' ],
            'storefuse-bridge-webhooks'   => [ 'flutter' ],
        ];

        if ( empty( $hidden_for[ $page_slug ] ) || ! in_array( $client, $hidden_for[ $page_slug ], true ) ) {
            return;
        }

        $client_labels = [
            'flutter' => __( 'Flutter', 'storefuse-bridge' ),
            'nextjs'  => __( 'Next.js', 'storefuse-bridge' ),
            'other'   => __( 'Other', 'storefuse-bridge' ),
        ];
        $client_label  = $client_labels[ $client ] ?? $client_labels['other'];
        ?>
        <div class="notice notice-info inline sfb-context-notice">
            <p>
                <?php
                printf(
                    /* translators: %s: configured primary client */
                    esc_html__( 'This page is hidden from your sidebar because your primary client is set to %s. You can still use and save these settings.', 'storefuse-bridge' ),
                    esc_html( $client_label )
                );
                ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=storefuse-bridge-setup' ) ); ?>">
                    <?php esc_html_e( 'Change client in Setup', 'storefuse-bridge' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    public function register_settings(): void {
        add_filter(
            'option_page_capability_storefuse_bridge_settings_group',
            static fn() => StoreFuse_Bridge_Admin::settings_capability()
        );
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
        if ( array_key_exists( 'app_primary_color', $input ) ) {
            $patch['app_primary_color'] = sanitize_hex_color( $input['app_primary_color'] ) ?: '#E85D04';
        }
        if ( array_key_exists( 'app_support_url', $input ) ) {
            $patch['app_support_url'] = esc_url_raw( $input['app_support_url'], [ 'https', 'http' ] );
        }

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
        if ( ! empty( $input['_sfb_clear_revalidation_secret'] ) ) {
            $patch['revalidation_secret'] = '';
        } elseif ( isset( $input['revalidation_secret'] ) && is_scalar( $input['revalidation_secret'] ) && (string) $input['revalidation_secret'] !== '' ) {
            $patch['revalidation_secret'] = sanitize_text_field( (string) $input['revalidation_secret'] );
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

        $patch = array_merge( $patch, StoreFuse_Bridge_App_Ui::sanitize_settings_patch( $input ) );

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

        $admin_css_path    = STOREFUSE_BRIDGE_PATH . 'assets/admin.css';
        $admin_js_path     = STOREFUSE_BRIDGE_PATH . 'assets/admin.js';
        $admin_css_version = file_exists( $admin_css_path ) ? (string) filemtime( $admin_css_path ) : STOREFUSE_BRIDGE_VERSION;
        $admin_js_version  = file_exists( $admin_js_path ) ? (string) filemtime( $admin_js_path ) : STOREFUSE_BRIDGE_VERSION;

        wp_enqueue_style(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.css',
            [],
            $admin_css_version
        );

        wp_enqueue_media();
        wp_enqueue_script( 'wc-enhanced-select' );
        wp_enqueue_style( 'woocommerce_admin_styles' );

        wp_enqueue_script(
            'storefuse-bridge-admin',
            STOREFUSE_BRIDGE_URL . 'assets/admin.js',
            [ 'jquery', 'wp-color-picker', 'media-upload' ],
            $admin_js_version,
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

        if ( ! current_user_can( self::settings_capability() ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        StoreFuse_Bridge_Cache::flush_all();
        wp_send_json_success( [ 'message' => __( 'All cache flushed.', 'storefuse-bridge' ) ] );
    }

    public function ajax_flush_cache_group(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( self::settings_capability() ) ) {
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

        if ( ! current_user_can( self::settings_capability() ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        delete_option( 'storefuse_bridge_webhook_log' );
        wp_send_json_success( [ 'message' => 'Webhook log cleared.' ] );
    }

    public function ajax_test_webhook(): void {
        check_ajax_referer( 'sfb_admin_nonce', 'nonce' );

        if ( ! current_user_can( self::settings_capability() ) ) {
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
        if ( current_user_can( self::settings_capability() ) ) {
            delete_transient( 'storefuse_bridge_show_setup_notice' );
        }
        wp_send_json_success();
    }

    public function page_dashboard(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-dashboard.php';
    }

    public function page_setup(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-setup.php';
    }

    public function page_branding(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-branding.php';
    }

    public function page_store_policies(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-store-policies.php';
    }

    public function page_webhooks(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-webhooks-isr.php';
    }

    public function page_app_layout(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-app-layout.php';
    }

    public function page_modules(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-modules.php';
    }

    public function page_general(): void {
        $this->page_branding();
    }

    public function page_homepage(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-homepage.php';
    }

    public function page_mobile_app(): void {
        $this->page_app_layout();
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
        $this->page_setup();
    }

    public function page_api(): void {
        require_once STOREFUSE_BRIDGE_PATH . 'admin/views/page-api.php';
    }

    public function page_advanced(): void {
        $this->page_modules();
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
        return [ 'settings', 'cart', 'checkout', 'orders', 'account' ];
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
