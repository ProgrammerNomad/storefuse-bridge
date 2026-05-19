<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce session lifecycle management.
 *
 * Handles:
 * - Guest-to-user cart merge on login (critical ecommerce requirement)
 * - Cart token for stateless clients (mobile apps)
 */
class StoreFuse_Bridge_Session {

    /**
     * Register WordPress/WooCommerce hooks.
     * Called once from the main plugin class.
     */
    public static function init(): void {
        add_action( 'wp_login', [ self::class, 'merge_guest_cart_after_login' ], 10, 2 );
    }

    /**
     * Merge the guest session cart into the user's account cart after login.
     *
     * Problem this solves: A customer adds items as a guest, then logs in.
     * Without merge logic the guest session is destroyed and the cart appears empty
     * — the customer sees their items disappear and abandons the purchase.
     *
     * WooCommerce handles the session migration internally; we ensure totals are
     * recalculated and fire an action so third-party plugins can react.
     *
     * @param string   $user_login
     * @param WP_User  $user
     */
    public static function merge_guest_cart_after_login( string $user_login, WP_User $user ): void {
        if ( ! WC()->cart ) {
            return;
        }

        // Capture what was in the guest cart before the session switches
        $guest_items = WC()->cart->get_cart();

        if ( empty( $guest_items ) ) {
            return;
        }

        // WC handles session migration via its own hooks;
        // we trigger a recalculation and expose the merge event.
        WC()->cart->maybe_set_cart_cookies();
        WC()->cart->calculate_totals();

        /**
         * Fires after a guest cart has been merged into a logged-in user's cart.
         *
         * @param int   $user_id     The user ID of the newly logged-in customer.
         * @param array $guest_items The cart items that were in the guest session.
         */
        do_action( 'storefuse_bridge_guest_cart_merged', $user->ID, $guest_items );
    }

    /**
     * Return the WooCommerce session customer ID (cart token).
     * Used in response headers so mobile clients can maintain session continuity.
     */
    public static function get_cart_token(): string {
        if ( ! ( WC()->session instanceof WC_Session ) ) {
            return '';
        }
        return (string) WC()->session->get_customer_id();
    }

    /**
     * Append the X-StoreFuse-Cart-Token header to a response.
     */
    public static function set_cart_token_header( WP_REST_Response $response ): WP_REST_Response {
        $response->header( 'X-StoreFuse-Cart-Token', self::get_cart_token() );
        return $response;
    }
}
