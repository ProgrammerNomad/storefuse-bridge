<?php
defined( 'ABSPATH' ) || exit;

/**
 * Downloads Module
 *
 * Routes (require login, no-store cache):
 *   GET /storefuse/v1/downloads  - list all downloadable files for current customer
 */
class StoreFuse_Bridge_Module_Downloads extends StoreFuse_Bridge_Module {

    protected string $id = 'downloads';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/downloads', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_downloads' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handler 

    public function get_downloads( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $raw       = wc_get_customer_available_downloads( get_current_user_id() );
        $downloads = array_map( [ $this, 'format_download' ], $raw );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( [ 'downloads' => $downloads ], 'storefuse.downloads.v1' )
        );
    }

    // ── Helpers 

    private function format_download( array $download ): array {
        $expires = $download['access_expires'] instanceof WC_DateTime
            ? StoreFuse_Bridge_Format::date( $download['access_expires']->format( 'Y-m-d H:i:s' ) )
            : null;

        return apply_filters( 'storefuse_bridge_download_item', [
            'download_id'        => $download['download_id'],
            'download_url'       => esc_url_raw( $download['download_url'] ),
            'product_id'         => (int) $download['product_id'],
            'product_name'       => $download['product_name'],
            'download_name'      => $download['download_name'],
            'order_id'           => (int) $download['order_id'],
            'order_key'          => $download['order_key'],
            'downloads_remaining'=> $download['downloads_remaining'], // string or 'unlimited'
            'access_expires'     => $expires,
        ], $download );
    }
}
