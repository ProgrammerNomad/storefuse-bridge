<?php
defined( 'ABSPATH' ) || exit;

/**
 * StoreFuse Bridge — Main Plugin Class
 *
 * Bootstraps all modules and registers the REST namespace.
 */
final class StoreFuse_Bridge {

    private static ?self $instance = null;

    /** @var StoreFuse_Bridge_Module[] */
    private array $modules = [];

    // Singleton
    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        $this->load_textdomain();
        $this->load_modules();
        $this->register_hooks();
    }

    // ── Text domain ──────────────────────────────────────────────────────────

    private function load_textdomain(): void {
        load_plugin_textdomain(
            'storefuse-bridge',
            false,
            dirname( STOREFUSE_BRIDGE_BASENAME ) . '/languages'
        );
    }

    // ── Modules ──────────────────────────────────────────────────────────────

    private function load_modules(): void {
        $this->modules = [
            new StoreFuse_Bridge_Module_Status(),
            new StoreFuse_Bridge_Module_Settings(),
            new StoreFuse_Bridge_Module_Products(),
            new StoreFuse_Bridge_Module_Categories(),
            new StoreFuse_Bridge_Module_Search(),
            new StoreFuse_Bridge_Module_Auth(),
            new StoreFuse_Bridge_Module_Cart(),
            new StoreFuse_Bridge_Module_Checkout(),
        ];

        foreach ( $this->modules as $module ) {
            if ( $module->is_enabled() ) {
                add_action( 'rest_api_init', [ $module, 'register_routes' ] );
            }
        }
    }

    // ── Hooks ────────────────────────────────────────────────────────────────

    private function register_hooks(): void {
        // Admin
        if ( is_admin() ) {
            new StoreFuse_Bridge_Admin();
        }

        // Session: merge guest cart after login
        StoreFuse_Bridge_Session::init();

        // Cache auto-invalidation hooks
        StoreFuse_Bridge_Cache::register_invalidation_hooks();

        // Register nav menu locations so admins can assign menus in Appearance → Menus
        add_action( 'after_setup_theme', function (): void {
            register_nav_menus( [
                'storefuse-header' => __( 'StoreFuse Header Navigation', 'storefuse-bridge' ),
                'storefuse-footer' => __( 'StoreFuse Footer Links', 'storefuse-bridge' ),
            ] );
        } );

        // Declare HPOS compatibility
        add_action( 'before_woocommerce_init', function (): void {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    STOREFUSE_BRIDGE_PATH . 'storefuse-bridge.php',
                    true
                );
            }
        } );
    }
}
