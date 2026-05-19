<?php
defined( 'ABSPATH' ) || exit;

/**
 * StoreFuse Bridge - Main Plugin Class
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

    // ── Modules 

    private function load_modules(): void {
        $this->modules = [
            new StoreFuse_Bridge_Module_Status(),
            new StoreFuse_Bridge_Module_Settings(),
            new StoreFuse_Bridge_Module_Products(),
            new StoreFuse_Bridge_Module_Categories(),
            new StoreFuse_Bridge_Module_Search(),
            new StoreFuse_Bridge_Module_Attributes(),
            new StoreFuse_Bridge_Module_Tags(),
            new StoreFuse_Bridge_Module_Auth(),
            new StoreFuse_Bridge_Module_Cart(),
            new StoreFuse_Bridge_Module_Checkout(),
            new StoreFuse_Bridge_Module_Account(),
            new StoreFuse_Bridge_Module_Orders(),
            new StoreFuse_Bridge_Module_Addresses(),
            new StoreFuse_Bridge_Module_Wishlist(),
            new StoreFuse_Bridge_Module_Reviews(),
            new StoreFuse_Bridge_Module_Posts(),
            new StoreFuse_Bridge_Module_Utils(),
            new StoreFuse_Bridge_Module_Downloads(),
            new StoreFuse_Bridge_Module_Webhooks(),
        ];

        foreach ( $this->modules as $module ) {
            if ( $module->is_enabled() ) {
                add_action( 'rest_api_init', [ $module, 'register_routes' ] );
            }
        }
    }

    // ── Hooks ───

    private function register_hooks(): void {
        // Admin
        if ( is_admin() ) {
            new StoreFuse_Bridge_Admin();
        }

        // Session: merge guest cart after login
        StoreFuse_Bridge_Session::init();

        // Cache auto-invalidation hooks
        StoreFuse_Bridge_Cache::register_invalidation_hooks();

        // Normalize all error responses from our namespace to the StoreFuse envelope.
        // This covers: WP_Error from permission_callback, WP core auth errors (rest_cookie_invalid_nonce),
        // and any other WP-generated error that reaches the client.
        add_filter( 'rest_post_dispatch', [ $this, 'normalize_error_response' ], 10, 3 );

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
    /**
     * Filter: normalize error responses from /storefuse/v1/* to the StoreFuse envelope.
     *
     * WordPress produces {code, message, data:{status}} for WP_Error-derived responses.
     * This filter reshapes those to {schema, api_version, error:{code, message, status}}
     * and maps WP-native error codes (rest_cookie_invalid_nonce etc.) to StoreFuse codes.
     *
     * Responses already in the StoreFuse envelope are left untouched (they lack a top-level
     * 'code' key, so the guard condition does not match).
     */
    public function normalize_error_response(
        WP_REST_Response $response,
        WP_REST_Server $server,
        WP_REST_Request $request
    ): WP_REST_Response {
        // Only touch our namespace.
        if ( strpos( $request->get_route(), '/storefuse/v1/' ) !== 0 ) {
            return $response;
        }

        $status = $response->get_status();
        $data   = $response->get_data();

        // Only reshape WP error envelopes (code + message at the top level, 4xx/5xx status).
        if ( $status < 400 || ! is_array( $data ) || ! isset( $data['code'], $data['message'] ) ) {
            return $response;
        }

        // Map WordPress-native error codes to StoreFuse codes.
        static $code_map = [
            'rest_cookie_invalid_nonce' => 'invalid_nonce',
            'rest_forbidden'            => 'forbidden',
            'rest_not_logged_in'        => 'not_authenticated',
            'rest_cannot_view'          => 'forbidden',
            'rest_login_required'       => 'not_authenticated',
        ];

        $code = $code_map[ $data['code'] ] ?? $data['code'];

        $response->set_data( [
            'schema'      => 'storefuse.error.v1',
            'api_version' => STOREFUSE_BRIDGE_VERSION,
            'error'       => [
                'code'    => $code,
                'message' => $data['message'],
                'status'  => $status,
            ],
        ] );

        return $response;
    }}
