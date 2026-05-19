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
    private function module_map(): array {
        return [
            'settings'   => (bool) StoreFuse_Bridge_Settings::get( 'module_settings_enabled',   true ),
            'products'   => (bool) StoreFuse_Bridge_Settings::get( 'module_products_enabled',   true ),
            'categories' => (bool) StoreFuse_Bridge_Settings::get( 'module_categories_enabled', true ),
            'search'     => (bool) StoreFuse_Bridge_Settings::get( 'module_search_enabled',     true ),
            'cart'       => (bool) StoreFuse_Bridge_Settings::get( 'module_cart_enabled',       true ),
            'checkout'   => (bool) StoreFuse_Bridge_Settings::get( 'module_checkout_enabled',   true ),
            'content'    => (bool) StoreFuse_Bridge_Settings::get( 'module_content_enabled',    true ),
            'webhooks'   => (bool) StoreFuse_Bridge_Settings::get( 'module_webhooks_enabled',   false ),
        ];
    }
}
