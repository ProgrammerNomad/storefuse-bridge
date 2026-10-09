<?php
defined( 'ABSPATH' ) || exit;

/**
 * Trusted client IP for rate limiting behind reverse proxies.
 */
class StoreFuse_Bridge_Http_Request {

    public static function client_ip( ?WP_REST_Request $request = null ): string {
        $remote = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

        if ( ! self::is_trusted_proxy( $remote ) ) {
            return sanitize_text_field( $remote );
        }

        $forwarded = '';
        if ( $request instanceof WP_REST_Request ) {
            $forwarded = (string) $request->get_header( 'x-forwarded-for' );
        }
        if ( $forwarded === '' && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $forwarded = (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        }

        if ( $forwarded !== '' ) {
            $parts = array_map( 'trim', explode( ',', $forwarded ) );
            $first = $parts[0] ?? '';
            if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
                return sanitize_text_field( $first );
            }
        }

        return sanitize_text_field( $remote );
    }

    private static function is_trusted_proxy( string $remote_addr ): bool {
        $trusted = apply_filters( 'storefuse_bridge_trusted_proxies', [] );
        if ( ! is_array( $trusted ) || $trusted === [] ) {
            return false;
        }
        foreach ( $trusted as $entry ) {
            $entry = (string) $entry;
            if ( $entry === $remote_addr ) {
                return true;
            }
            if ( str_contains( $entry, '/' ) && self::ip_in_cidr( $remote_addr, $entry ) ) {
                return true;
            }
        }
        return false;
    }

    private static function ip_in_cidr( string $ip, string $cidr ): bool {
        if ( ! str_contains( $cidr, '/' ) ) {
            return false;
        }
        [ $subnet, $mask ] = explode( '/', $cidr, 2 );
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) {
            return false;
        }
        $mask = (int) $mask;
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $ip_long     = ip2long( $ip );
            $subnet_long = ip2long( $subnet );
            if ( $ip_long === false || $subnet_long === false ) {
                return false;
            }
            $mask_long = -1 << ( 32 - $mask );
            return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
        }
        return false;
    }
}
