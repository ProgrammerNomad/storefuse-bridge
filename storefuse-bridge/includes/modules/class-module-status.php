<?php
defined( 'ABSPATH' ) || exit;

/**
 * Status Module - GET /storefuse/v1/status
 *
 * Health check. Confirms the plugin is active, lists enabled modules,
 * and exposes a feature capability map so the storefront can adapt
 * at runtime without hardcoded checks.
 */
class StoreFuse_Bridge_Module_Status extends StoreFuse_Bridge_Module {

    protected string $id = 'status';

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/status',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_status' ],
                'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'public_permission' ],
            ]
        );
    }

    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $data = [
            'status'        => 'ok',
            'plugin'        => 'StoreFuse Bridge',
            'version'       => STOREFUSE_BRIDGE_VERSION,
            'wordpress'     => StoreFuse_Bridge_WC_Compat::wp_version(),
            'woocommerce'   => StoreFuse_Bridge_WC_Compat::wc_version(),
            'php'           => PHP_VERSION,
            'site_url'      => get_site_url(),
            'api_namespace' => 'storefuse/v1',
            'modules'       => $this->module_map(),
            'features'      => StoreFuse_Bridge_WC_Compat::features(),
        ];

        /**
         * Filter the status response data.
         *
         * @param array           $data    The status data.
         * @param WP_REST_Request $request The current request.
         */
        $data = apply_filters( 'storefuse_bridge_status_response', $data, $request );

        $response = $this->success( $data, 'storefuse.status.v1' );

        // Status endpoint is public but should not be CDN-cached
        // (it reflects live plugin state). Short browser cache is fine.
        $response->header( 'Cache-Control', 'public, max-age=60' );

        return $response;
    }

    /**
     * Build a map of module_id => bool indicating which modules are enabled.
     *
     * @return array<string, bool>
     */
    public static function get_module_map(): array {
        return ( new self() )->module_map();
    }

    /**
     * @return array<string, bool>
     */
    private function module_map(): array {
        $map = [
            'status' => true,
            'auth'   => true,
        ];

        $toggleable = [
            'settings'   => StoreFuse_Bridge_Module_Settings::class,
            'products'   => StoreFuse_Bridge_Module_Products::class,
            'categories' => StoreFuse_Bridge_Module_Categories::class,
            'search'     => StoreFuse_Bridge_Module_Search::class,
            'attributes' => StoreFuse_Bridge_Module_Attributes::class,
            'tags'       => StoreFuse_Bridge_Module_Tags::class,
            'cart'       => StoreFuse_Bridge_Module_Cart::class,
            'checkout'   => StoreFuse_Bridge_Module_Checkout::class,
            'account'    => StoreFuse_Bridge_Module_Account::class,
            'orders'     => StoreFuse_Bridge_Module_Orders::class,
            'addresses'  => StoreFuse_Bridge_Module_Addresses::class,
            'wishlist'   => StoreFuse_Bridge_Module_Wishlist::class,
            'reviews'    => StoreFuse_Bridge_Module_Reviews::class,
            'posts'      => StoreFuse_Bridge_Module_Posts::class,
            'utils'      => StoreFuse_Bridge_Module_Utils::class,
            'downloads'  => StoreFuse_Bridge_Module_Downloads::class,
            'webhooks'   => StoreFuse_Bridge_Module_Webhooks::class,
        ];

        foreach ( $toggleable as $id => $class ) {
            $map[ $id ] = ( new $class() )->is_enabled();
        }

        return $map;
    }
}
