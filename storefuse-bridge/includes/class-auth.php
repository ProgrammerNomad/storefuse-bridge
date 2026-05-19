<?php
defined( 'ABSPATH' ) || exit;

/**
 * Nonce validation and session permission callbacks.
 *
 * Used as permission_callback on cart and checkout endpoints.
 * Customer-facing auth (is_user_logged_in) is in StoreFuse_Bridge_Permissions.
 */
class StoreFuse_Bridge_Auth {

    /**
     * Permission callback for public endpoints (products, categories, search, settings).
     * Always returns true - no auth required.
     */
    public static function public_permission(): bool {
        return true;
    }

    /**
     * Permission callback for cart write endpoints (add, update, remove, coupon).
     * Requires a valid X-WC-Nonce header.
     */
    public static function cart_permission( WP_REST_Request $request ): bool|WP_REST_Response {
        if ( ! self::validate_nonce( $request, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return true;
    }

    /**
     * Permission callback for checkout.
     * Requires a valid nonce AND an active WooCommerce cart session.
     */
    public static function checkout_permission( WP_REST_Request $request ): bool|WP_REST_Response {
        if ( ! self::validate_nonce( $request, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        if ( ! self::validate_cart_session() ) {
            return StoreFuse_Bridge_Errors::validation_error( 'No active cart session.' );
        }
        return true;
    }

    /**
     * Permission callback for auth write endpoints (login, register, logout, etc.).
     * Requires X-WP-Nonce header (standard WordPress REST nonce).
     */
    public static function auth_write_permission( WP_REST_Request $request ): bool|WP_REST_Response {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return true;
    }

    // ── Helpers 

    /**
     * Validate the nonce sent in the X-WC-Nonce header.
     *
     * @param string $action  WordPress nonce action to verify against
     */
    public static function validate_nonce( WP_REST_Request $request, string $action = 'wc_store_api' ): bool {
        $nonce = $request->get_header( 'X-WC-Nonce' );
        if ( ! $nonce ) {
            return false;
        }
        return (bool) wp_verify_nonce( $nonce, $action );
    }

    /**
     * Check that a WooCommerce session is active and has a session cookie.
     */
    public static function validate_cart_session(): bool {
        if ( ! ( WC()->session instanceof WC_Session ) ) {
            return false;
        }
        return (bool) WC()->session->get_session_cookie();
    }

    /**
     * Generate a nonce for the frontend to use in subsequent requests.
     * Called on the bootstrap endpoint so the storefront can include it in headers.
     */
    public static function generate_storefront_nonce(): string {
        return wp_create_nonce( 'wc_store_api' );
    }
}
