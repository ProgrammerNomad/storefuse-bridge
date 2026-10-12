<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared REST response builder.
 *
 * Every response from StoreFuse Bridge uses the same envelope:
 * {
 *   "schema":      "storefuse.{resource}.v1",
 *   "api_version": "1.0.0",
 *   "data":        { ... }
 * }
 */
class StoreFuse_Bridge_Response {

    /**
     * Build a success response.
     *
     * @param mixed  $data
     * @param string $schema  e.g. 'storefuse.products.v1'
     * @param int    $status  HTTP status code (default 200)
     */
    public static function success( mixed $data, string $schema, int $status = 200 ): WP_REST_Response {
        $body = [
            'schema'      => $schema,
            'api_version' => STOREFUSE_BRIDGE_VERSION,
            'data'        => $data,
        ];

        $response = new WP_REST_Response( $body, $status );
        $response->header( 'X-StoreFuse-Bridge-Version', STOREFUSE_BRIDGE_VERSION );

        return $response;
    }

    /**
     * Add public cache headers to a response.
     * Safe for CDN / shared caching. Use only on endpoints with no session data.
     *
     * @param WP_REST_Response $response
     * @param int              $max_age   seconds
     */
    public static function with_public_cache( WP_REST_Response $response, int $max_age = 600 ): WP_REST_Response {
        $response->header( 'Cache-Control', "public, max-age={$max_age}, s-maxage={$max_age}" );
        $response->header( 'X-StoreFuse-Cache', 'MISS' );
        return $response;
    }

    /**
     * Mark a response as served from cache.
     */
    public static function mark_cache_hit( WP_REST_Response $response ): WP_REST_Response {
        $response->header( 'X-StoreFuse-Cache', 'HIT' );
        return $response;
    }

    /**
     * Add no-store headers to a response.
     * Required for all session/auth/cart/account endpoints.
     */
    public static function with_no_store( WP_REST_Response $response ): WP_REST_Response {
        $response->header( 'Cache-Control', 'no-store' );
        return $response;
    }

    /**
     * v1 versioning / future deprecation headers (see docs/api-versioning.md).
     */
    public static function with_version_policy( WP_REST_Response $response ): WP_REST_Response {
        $response->header( 'X-StoreFuse-API-Version', 'v1' );

        $successor = apply_filters( 'storefuse_bridge_api_successor_link', '' );
        if ( is_string( $successor ) && $successor !== '' ) {
            $response->header( 'Link', '<' . $successor . '>; rel="successor-version"' );
        }

        $sunset = apply_filters( 'storefuse_bridge_v1_sunset', '' );
        if ( is_string( $sunset ) && $sunset !== '' ) {
            $response->header( 'Deprecation', 'true' );
            $response->header( 'Sunset', $sunset );
        }

        return $response;
    }

    /**
     * Weak ETag + If-None-Match for publicly cacheable JSON (CDN/browser coordination).
     */
    public static function apply_conditional_etag( WP_REST_Response $response, WP_REST_Request $request ): WP_REST_Response {
        $headers = $response->get_headers();
        $cache   = $headers['Cache-Control'] ?? '';
        if ( ! is_string( $cache ) || ! str_contains( $cache, 'public' ) ) {
            return $response;
        }

        $data = $response->get_data();
        if ( ! is_array( $data ) ) {
            return $response;
        }

        $etag = 'W/"' . md5( (string) wp_json_encode( $data ) ) . '"';
        $response->header( 'ETag', $etag );

        $inm = $request->get_header( 'if-none-match' );
        if ( $inm && trim( $inm ) === $etag ) {
            $not_modified = new WP_REST_Response( null, 304 );
            $not_modified->header( 'ETag', $etag );
            foreach ( [ 'Cache-Control', 'X-StoreFuse-Cache', 'X-StoreFuse-Bridge-Version', 'X-StoreFuse-API-Version' ] as $name ) {
                if ( isset( $headers[ $name ] ) ) {
                    $not_modified->header( $name, $headers[ $name ] );
                }
            }
            return self::with_version_policy( $not_modified );
        }

        return $response;
    }
}
