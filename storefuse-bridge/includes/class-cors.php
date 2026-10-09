<?php
defined( 'ABSPATH' ) || exit;

/**
 * CORS headers for StoreFuse REST routes (browser clients with credentials).
 */
class StoreFuse_Bridge_Cors {

    public static function init(): void {
        add_filter( 'rest_pre_dispatch', [ self::class, 'handle_preflight' ], 10, 3 );
        add_filter( 'rest_pre_serve_request', [ self::class, 'maybe_send_cors_headers' ], 10, 4 );
    }

    /**
     * @param mixed            $result
     * @param WP_REST_Server   $server
     * @param WP_REST_Request  $request
     * @return mixed
     */
    public static function handle_preflight( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
        if ( $request->get_method() !== 'OPTIONS' ) {
            return $result;
        }
        if ( strpos( $request->get_route(), '/storefuse/v1/' ) !== 0 ) {
            return $result;
        }
        self::send_cors_headers();
        return new WP_REST_Response( null, 204 );
    }

    /**
     * @param bool              $served
     * @param WP_HTTP_Response  $result
     * @param WP_REST_Request   $request
     * @param WP_REST_Server    $server
     */
    public static function maybe_send_cors_headers(
        bool $served,
        WP_HTTP_Response $result,
        WP_REST_Request $request,
        WP_REST_Server $server
    ): bool {
        if ( strpos( $request->get_route(), '/storefuse/v1/' ) !== 0 ) {
            return $served;
        }
        self::send_cors_headers();
        return $served;
    }

    private static function send_cors_headers(): void {
        if ( ! (bool) StoreFuse_Bridge_Settings::get( 'cors_enabled', false ) ) {
            return;
        }

        $origin       = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
        $allowed      = self::allowed_origins();
        $origin_match = $origin && in_array( $origin, $allowed, true );

        if ( $origin_match ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Vary: Origin', false );
        }

        header(
            'Access-Control-Expose-Headers: X-WP-Nonce, X-WC-Nonce, X-StoreFuse-Cart-Token, X-StoreFuse-Cache, X-StoreFuse-Bridge-Version'
        );

        if ( $origin_match ) {
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
            header(
                'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-WC-Nonce, X-StoreFuse-Cart-Token, Idempotency-Key'
            );
            header( 'Access-Control-Max-Age: 86400' );
        }
    }

    /**
     * @return list<string>
     */
    public static function allowed_origins(): array {
        $raw   = (string) StoreFuse_Bridge_Settings::get( 'cors_allowed_origins', '' );
        $lines = preg_split( '/\r\n|\r|\n/', $raw ) ?: [];
        $origins = [];
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                continue;
            }
            $origins[] = esc_url_raw( $line );
        }
        return array_values( array_unique( array_filter( $origins ) ) );
    }
}
