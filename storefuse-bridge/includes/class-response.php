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
}
