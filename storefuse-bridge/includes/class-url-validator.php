<?php
defined( 'ABSPATH' ) || exit;

/**
 * Outbound URL safety checks (SSRF mitigation for webhooks).
 */
class StoreFuse_Bridge_Url_Validator {

    /**
     * @return true|WP_Error
     */
    public static function validate_webhook_base_url( string $url ): bool|WP_Error {
        $url = trim( $url );
        if ( $url === '' ) {
            return new WP_Error( 'invalid_url', 'URL is empty.' );
        }

        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
            return new WP_Error( 'invalid_url', 'URL is malformed.' );
        }

        if ( strtolower( $parts['scheme'] ) !== 'https' ) {
            return new WP_Error( 'invalid_url', 'Webhook URL must use HTTPS.' );
        }

        $host = strtolower( $parts['host'] );
        if ( $host === 'localhost' || str_ends_with( $host, '.localhost' ) ) {
            return new WP_Error( 'invalid_url', 'Localhost URLs are not allowed.' );
        }

        $ips = self::resolve_host_ips( $host );
        foreach ( $ips as $ip ) {
            if ( self::is_private_or_reserved_ip( $ip ) ) {
                return new WP_Error( 'invalid_url', 'Private or reserved network addresses are not allowed.' );
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function resolve_host_ips( string $host ): array {
        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            return [ $host ];
        }

        $records = @dns_get_record( $host, DNS_A + DNS_AAAA );
        if ( ! is_array( $records ) ) {
            return [];
        }

        $ips = [];
        foreach ( $records as $record ) {
            if ( ! empty( $record['ip'] ) ) {
                $ips[] = $record['ip'];
            }
            if ( ! empty( $record['ipv6'] ) ) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values( array_unique( $ips ) );
    }

    private static function is_private_or_reserved_ip( string $ip ): bool {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
