<?php
defined( 'ABSPATH' ) || exit;

/**
 * Wishlist Module
 *
 * Stores wishlisted product IDs in WordPress user meta (key: storefuse_wishlist).
 * No external plugin dependency required.
 *
 * Routes (all require login):
 *   GET    /storefuse/v1/wishlist           - get wishlist items
 *   POST   /storefuse/v1/wishlist/add       - add a product (X-WP-Nonce)
 *   DELETE /storefuse/v1/wishlist/remove    - remove a product (X-WP-Nonce)
 */
class StoreFuse_Bridge_Module_Wishlist extends StoreFuse_Bridge_Module {

    protected string $id = 'wishlist';

    private const META_KEY = 'storefuse_wishlist';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/wishlist', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_wishlist' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );

        register_rest_route( $this->namespace, '/wishlist/add', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'add_to_wishlist' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
            'args'                => [
                'product_id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
            ],
        ] );

        register_rest_route( $this->namespace, '/wishlist/remove', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'remove_from_wishlist' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
            'args'                => [
                'product_id' => [ 'required' => true, 'type' => 'integer', 'minimum' => 1 ],
            ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_wishlist( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [ 'items' => $this->build_wishlist_items( get_current_user_id() ) ],
                'storefuse.wishlist.v1'
            )
        );
    }

    public function add_to_wishlist( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $product_id = (int) $request->get_param( 'product_id' );
        $product    = wc_get_product( $product_id );

        if ( ! $product ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $user_id  = get_current_user_id();
        $wishlist = $this->get_raw_ids( $user_id );

        if ( ! in_array( $product_id, $wishlist, true ) ) {
            $wishlist[] = $product_id;
            update_user_meta( $user_id, self::META_KEY, $wishlist );
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [ 'items' => $this->build_wishlist_items( $user_id ) ],
                'storefuse.wishlist.v1'
            )
        );
    }

    public function remove_from_wishlist( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $product_id = (int) $request->get_param( 'product_id' );
        $user_id    = get_current_user_id();

        $wishlist = array_values( array_filter(
            $this->get_raw_ids( $user_id ),
            static fn( $id ) => $id !== $product_id
        ) );

        update_user_meta( $user_id, self::META_KEY, $wishlist );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [ 'items' => $this->build_wishlist_items( $user_id ) ],
                'storefuse.wishlist.v1'
            )
        );
    }

    // ── Helpers 

    private function check_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    /** @return int[] */
    private function get_raw_ids( int $user_id ): array {
        $meta = get_user_meta( $user_id, self::META_KEY, true );
        return is_array( $meta ) ? array_map( 'intval', $meta ) : [];
    }

    private function build_wishlist_items( int $user_id ): array {
        $ids   = $this->get_raw_ids( $user_id );
        $items = [];

        foreach ( $ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product || ! $product->is_visible() ) {
                continue;
            }

            $items[] = apply_filters( 'storefuse_bridge_wishlist_item', [
                'product_id'  => $product_id,
                'name'        => $product->get_name(),
                'slug'        => $product->get_slug(),
                'href'        => '/product/' . $product->get_slug(),
                'price'       => StoreFuse_Bridge_Format::price( $product->get_price() ),
                'sale_price'  => $product->is_on_sale()
                    ? StoreFuse_Bridge_Format::price( $product->get_sale_price() )
                    : null,
                'thumbnail'   => StoreFuse_Bridge_Format::image( (int) $product->get_image_id() ?: null ),
                'is_in_stock' => $product->is_in_stock(),
                'is_on_sale'  => $product->is_on_sale(),
            ], $product );
        }

        return $items;
    }
}
