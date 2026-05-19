<?php
defined( 'ABSPATH' ) || exit;

/**
 * Permission callbacks for customer-authenticated endpoints.
 *
 * Handles: account, orders, addresses, wishlist, downloads.
 * Separate from StoreFuse_Bridge_Auth (which handles nonce/session validation).
 */
class StoreFuse_Bridge_Permissions {

    /**
     * Require the user to be logged in.
     * Used as permission_callback on all customer endpoints.
     *
     * Returns WP_Error so WordPress REST dispatcher actually blocks the request.
     * WP_REST_Response is truthy and is silently ignored by permission_callback handling.
     *
     * @return true|WP_Error
     */
    public static function require_login( WP_REST_Request $request ): bool|WP_Error {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_authenticated', 'You must be logged in to access this resource.', [ 'status' => 401 ] );
        }
        return true;
    }

    /**
     * Require login AND a valid X-WP-Nonce for write operations.
     * Used on: PUT /account, PUT /addresses/*, POST /account/change-password.
     *
     * @return true|WP_Error
     */
    public static function require_login_and_nonce( WP_REST_Request $request ): bool|WP_Error {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_authenticated', 'You must be logged in to access this resource.', [ 'status' => 401 ] );
        }

        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'invalid_nonce', 'Security token missing or invalid.', [ 'status' => 403 ] );
        }

        return true;
    }

    /**
     * Check the logged-in user owns a given order.
     * Called inside the order callback - not as a permission_callback, because
     * we need the order ID from the URL parameter.
     */
    public static function can_manage_order( int $order_id ): bool {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }
        return (int) $order->get_customer_id() === get_current_user_id();
    }
}
