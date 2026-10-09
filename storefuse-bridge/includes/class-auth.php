<?php
defined( 'ABSPATH' ) || exit;

/**
 * Nonce validation and session permission callbacks.
 *
 * Used as permission_callback on cart and checkout endpoints.
 * Customer-facing auth (is_user_logged_in) is in StoreFuse_Bridge_Permissions.
 */
class StoreFuse_Bridge_Auth {

    public static function init(): void {
        add_filter( 'determine_current_user', [ self::class, 'authenticate_application_password' ], 20 );
    }

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
     *
     * Returns WP_Error (not WP_REST_Response) so WordPress REST dispatcher actually blocks
     * the request. WP_REST_Response is truthy and would be ignored by permission_callback.
     */
    public static function cart_permission( WP_REST_Request $request ): bool|WP_Error {
        if ( ! self::validate_nonce( $request, 'wc_store_api' ) ) {
            return new WP_Error( 'invalid_nonce', 'Security token missing or invalid.', [ 'status' => 403 ] );
        }
        return true;
    }

    /**
     * Permission callback for checkout.
     * Requires a valid nonce AND an active WooCommerce cart session.
     */
    public static function checkout_permission( WP_REST_Request $request ): bool|WP_Error {
        if ( ! self::validate_nonce( $request, 'wc_store_api' ) ) {
            return new WP_Error( 'invalid_nonce', 'Security token missing or invalid.', [ 'status' => 403 ] );
        }
        if ( ! self::validate_cart_session() ) {
            return new WP_Error( 'checkout_no_session', 'No active cart session.', [ 'status' => 403 ] );
        }
        return true;
    }

    /**
     * Permission callback for auth write endpoints (login, register, logout, etc.).
     * Requires X-WP-Nonce header (standard WordPress REST nonce).
     */
    public static function auth_write_permission( WP_REST_Request $request ): bool|WP_Error {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'invalid_nonce', 'Security token missing or invalid.', [ 'status' => 403 ] );
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
        if ( WC()->session->get_session_cookie() ) {
            return true;
        }
        if ( class_exists( 'StoreFuse_Bridge_Cart_Session_Token' ) && StoreFuse_Bridge_Cart_Session_Token::has_active_restore() ) {
            return (string) WC()->session->get_customer_id() !== '';
        }
        return false;
    }

    /**
     * Generate a nonce for the frontend to use in subsequent requests.
     * Called on the bootstrap endpoint so the storefront can include it in headers.
     */
    public static function generate_storefront_nonce(): string {
        return wp_create_nonce( 'wc_store_api' );
    }

    /**
     * Nonce pair for guest bootstrap (GET /auth/nonce, optional GET /auth/me).
     *
     * @return array{nonce: string, cart_nonce: string}
     */
    public static function bootstrap_nonces(): array {
        self::ensure_cart();

        return [
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'cart_nonce' => self::generate_storefront_nonce(),
        ];
    }

    /**
     * Validate X-WC-Nonce for cart/checkout write handlers.
     *
     * @return WP_REST_Response|null Null when valid.
     */
    public static function check_cart_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        if ( ! self::validate_nonce( $request, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    /**
     * Validate X-WP-Nonce for authenticated account write handlers.
     *
     * @return WP_REST_Response|null Null when valid.
     */
    public static function check_wp_rest_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    /**
     * Ensure WooCommerce session and cart exist (REST requests skip the `wp` hook).
     */
    public static function ensure_cart(): void {
        if ( ! WC()->cart ) {
            wc_load_cart();
            if ( ! WC()->cart ) {
                StoreFuse_Bridge_Logger::error( 'WooCommerce cart session failed to initialize', [] );
            }
        }
    }

    /**
     * Optional throttle for auth endpoints (filterable limits).
     *
     * @return WP_REST_Response|null Null when allowed.
     */
    public static function is_throttled( string $action, string $identifier ): bool {
        $max     = (int) apply_filters( 'storefuse_bridge_auth_max_attempts', 10, $action );
        $key     = 'sfb_throttle_' . sanitize_key( $action ) . '_' . md5( $identifier );
        return (int) get_transient( $key ) >= $max;
    }

    public static function record_throttle_failure( string $action, string $identifier ): void {
        $window  = (int) apply_filters( 'storefuse_bridge_auth_throttle_window', 900, $action );
        $key     = 'sfb_throttle_' . sanitize_key( $action ) . '_' . md5( $identifier );
        $attempt = (int) get_transient( $key );
        set_transient( $key, $attempt + 1, $window );
    }

    /**
     * Pre-check + increment (nonce endpoint and non-credential limits).
     *
     * @return WP_REST_Response|null Null when allowed.
     */
    public static function throttle( string $action, string $identifier ): ?WP_REST_Response {
        if ( self::is_throttled( $action, $identifier ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Too many attempts. Please try again later.' );
        }
        self::record_throttle_failure( $action, $identifier );
        return null;
    }

    /**
     * WordPress Application Passwords (Basic or Bearer user:password) for mobile clients.
     *
     * @param int|false $user_id
     * @return int|false
     */
    public static function authenticate_application_password( $user_id ) {
        if ( $user_id ) {
            return $user_id;
        }

        if ( ! self::is_storefuse_rest_request() || ! function_exists( 'wp_authenticate_application_password' ) ) {
            return $user_id;
        }

        $header = self::authorization_header();
        if ( $header === '' ) {
            return $user_id;
        }

        $username = '';
        $password = '';

        if ( preg_match( '/^Basic\s+(.+)$/i', $header, $matches ) ) {
            $decoded = base64_decode( $matches[1], true );
            if ( $decoded && str_contains( $decoded, ':' ) ) {
                [ $username, $password ] = array_pad( explode( ':', $decoded, 2 ), 2, '' );
            }
        } elseif ( preg_match( '/^Bearer\s+(.+)$/i', $header, $matches ) ) {
            $token = trim( $matches[1] );
            $decoded = base64_decode( $token, true );
            if ( $decoded && str_contains( $decoded, ':' ) ) {
                [ $username, $password ] = array_pad( explode( ':', $decoded, 2 ), 2, '' );
            }
        }

        if ( $username === '' || $password === '' ) {
            return $user_id;
        }

        $authenticated = wp_authenticate_application_password( null, $username, $password );
        if ( $authenticated instanceof WP_User ) {
            return $authenticated->ID;
        }

        return $user_id;
    }

    private static function is_storefuse_rest_request(): bool {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
            return str_contains( $uri, '/storefuse/v1/' );
        }
        return false;
    }

    private static function authorization_header(): string {
        if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            return trim( (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
        }
        if ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            return trim( (string) wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
        }
        return '';
    }
}
