<?php
defined( 'ABSPATH' ) || exit;

/**
 * Signed cart session token (v1.1+) for mobile clients without WC cookies.
 */
class StoreFuse_Bridge_Cart_Session_Token {

    private const TRANSIENT_PREFIX = 'sfb_cart_tok_';
    private const TOKEN_TTL = WEEK_IN_SECONDS;
    private const VERSION = '1';

    private static string $restore_customer_id = '';

    public static function init(): void {
        add_filter( 'rest_pre_dispatch', [ self::class, 'maybe_restore_session' ], 4, 3 );
        add_filter( 'woocommerce_session_customer_id', [ self::class, 'filter_customer_id' ], 1 );
        add_action( 'wp_logout', [ self::class, 'revoke_current_token' ] );
    }

    public static function filter_customer_id( string $customer_id ): string {
        if ( self::$restore_customer_id !== '' ) {
            return self::$restore_customer_id;
        }
        return $customer_id;
    }

    public static function has_active_restore(): bool {
        return self::$restore_customer_id !== '';
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    public static function maybe_restore_session( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
        if ( strpos( $request->get_route(), '/storefuse/v1/' ) !== 0 ) {
            return $result;
        }

        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return $result;
        }

        if ( WC()->session->get_session_cookie() ) {
            return $result;
        }

        $header = (string) $request->get_header( 'x-storefuse-cart-token' );
        if ( $header === '' ) {
            return $result;
        }

        $customer_id = self::validate_and_resolve( $header );
        if ( $customer_id === '' ) {
            return $result;
        }

        self::$restore_customer_id = $customer_id;
        StoreFuse_Bridge_Auth::ensure_cart();

        return $result;
    }

    /**
     * Issue a signed token bound to the current WC session customer id.
     */
    public static function issue_for_current_session(): string {
        if ( ! ( WC()->session instanceof WC_Session ) ) {
            return '';
        }
        $customer_id = (string) WC()->session->get_customer_id();
        if ( $customer_id === '' ) {
            return '';
        }

        $expires = time() + self::TOKEN_TTL;
        $payload = self::VERSION . '|' . $customer_id . '|' . (string) $expires;
        $sig     = hash_hmac( 'sha256', $payload, self::secret() );
        $token   = base64_encode( $payload . '|' . $sig ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

        set_transient( self::TRANSIENT_PREFIX . md5( $customer_id ), 1, self::TOKEN_TTL );

        return rtrim( strtr( $token, '+/', '-_' ), '=' );
    }

    /**
     * Public token for response header (signed, not raw customer id).
     */
    public static function get_header_value(): string {
        $issued = self::issue_for_current_session();
        return $issued !== '' ? $issued : (string) StoreFuse_Bridge_Session::get_cart_token_legacy();
    }

    public static function validate_and_resolve( string $token ): string {
        $token = strtr( $token, '-_', '+/' );
        $pad   = strlen( $token ) % 4;
        if ( $pad > 0 ) {
            $token .= str_repeat( '=', 4 - $pad );
        }
        $decoded = base64_decode( $token, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        if ( ! is_string( $decoded ) || ! str_contains( $decoded, '|' ) ) {
            return '';
        }

        $parts = explode( '|', $decoded );
        if ( count( $parts ) !== 4 ) {
            return '';
        }

        [ $version, $customer_id, $expires, $sig ] = $parts;
        if ( $version !== self::VERSION ) {
            return '';
        }

        if ( (int) $expires < time() ) {
            return '';
        }

        $payload = $version . '|' . $customer_id . '|' . $expires;
        $expect  = hash_hmac( 'sha256', $payload, self::secret() );
        if ( ! hash_equals( $expect, $sig ) ) {
            return '';
        }

        if ( ! get_transient( self::TRANSIENT_PREFIX . md5( $customer_id ) ) ) {
            return '';
        }

        return sanitize_text_field( $customer_id );
    }

    public static function revoke_current_token(): void {
        if ( WC()->session instanceof WC_Session ) {
            $customer_id = (string) WC()->session->get_customer_id();
            if ( $customer_id !== '' ) {
                delete_transient( self::TRANSIENT_PREFIX . md5( $customer_id ) );
            }
        }
    }

    private static function secret(): string {
        return (string) apply_filters( 'storefuse_bridge_cart_token_secret', wp_salt( 'storefuse_bridge_cart' ) );
    }
}
