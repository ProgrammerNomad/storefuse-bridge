<?php
defined( 'ABSPATH' ) || exit;

/** Single-use native-to-browser checkout session exchange. */
final class StoreFuse_Bridge_Checkout_Handoff {
    public static function init(): void {
        add_action( 'init', [ self::class, 'consume' ], 1 );
    }

    public static function issue(): array {
        $token = bin2hex( random_bytes( 32 ) );
        $cookies = [];
        $session_name = (string) apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );
        foreach ( [ LOGGED_IN_COOKIE, $session_name ] as $name ) {
            if ( ! empty( $_COOKIE[ $name ] ) ) {
                $cookies[ $name ] = wp_unslash( $_COOKIE[ $name ] );
            }
        }
        set_transient( 'sfb_handoff_' . hash( 'sha256', $token ), [
            'cookies' => $cookies,
            'user_id' => get_current_user_id(),
            'expires' => time() + 60,
        ], 60 );
        return [
            'handoff_url' => add_query_arg( 'storefuse_checkout', '1', home_url( '/' ) ),
            'handoff_token' => $token,
            'payment_origins' => array_values( (array) apply_filters( 'storefuse_bridge_payment_origins', [] ) ),
        ];
    }

    public static function consume(): void {
        if ( ! isset( $_GET['storefuse_checkout'] ) ) {
            return;
        }
        nocache_headers();
        header( 'Referrer-Policy: no-referrer' );
        header( 'X-Content-Type-Options: nosniff' );
        $origin = get_http_origin();
        $site = wp_parse_url( home_url() );
        $expected = $site['scheme'] . '://' . $site['host'] . ( isset( $site['port'] ) ? ':' . $site['port'] : '' );
        if ( ( $origin && $origin !== $expected ) || ( $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '' ) === 'cross-site' ) {
            wp_die( 'Cross-site checkout handoff is not allowed.', '', [ 'response' => 403 ] );
        }
        $token = isset( $_POST['token'] ) ? (string) wp_unslash( $_POST['token'] ) : '';
        if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
            wp_die( 'Checkout link is invalid or expired. Return to the app and try again.', '', [ 'response' => 403 ] );
        }
        $key = 'sfb_handoff_' . hash( 'sha256', $token );
        if ( ! StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $key ) ) {
            wp_die( 'Checkout link is already in use.', '', [ 'response' => 409 ] );
        }
        try {
            $data = get_transient( $key );
            delete_transient( $key );
        } finally {
            StoreFuse_Bridge_Checkout_Idempotency::release_lock( $key );
        }
        if ( ! is_array( $data ) || $data['expires'] < time() ) {
            wp_die( 'Checkout link has expired. Return to the app and try again.', '', [ 'response' => 403 ] );
        }
        $auth_cookie = $data['cookies'][ LOGGED_IN_COOKIE ] ?? '';
        if ( $data['user_id'] && (int) wp_validate_auth_cookie( $auth_cookie, 'logged_in' ) !== (int) $data['user_id'] ) {
            wp_die( 'Your session has expired. Sign in again in the app.', '', [ 'response' => 403 ] );
        }
        // HttpOnly is retained. Tokens and cookies are never put in redirect URLs.
        foreach ( $data['cookies'] as $name => $value ) {
            setcookie( $name, $value, [
                'expires' => 0,
                'path' => COOKIEPATH ?: '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ] );
        }
        $url = StoreFuse_Bridge_Module_Checkout::checkout_url();
        wp_safe_redirect( $url, 303 );
        exit;
    }
}
