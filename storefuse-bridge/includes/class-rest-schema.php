<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared REST route argument schemas (OpenAPI-aligned).
 */
class StoreFuse_Bridge_Rest_Schema {

    /**
     * Standard list pagination query params used by products, search, orders, posts, reviews.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pagination_query_args( int $default_per_page = 12, int $max_per_page = 100 ): array {
        return [
            'page'     => [
                'description'       => '1-based page index.',
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'description'       => 'Items per page.',
                'type'              => 'integer',
                'default'           => $default_per_page,
                'minimum'           => 1,
                'maximum'           => $max_per_page,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * Pagination meta object returned in list envelopes (`data.meta`).
     *
     * @return array<string, mixed>
     */
    public static function pagination_meta_schema(): array {
        return [
            'type'       => 'object',
            'required'   => [ 'total', 'total_pages', 'page', 'per_page' ],
            'properties' => [
                'total'       => [ 'type' => 'integer', 'minimum' => 0 ],
                'total_pages' => [ 'type' => 'integer', 'minimum' => 0 ],
                'page'        => [ 'type' => 'integer', 'minimum' => 1 ],
                'per_page'    => [ 'type' => 'integer', 'minimum' => 1 ],
            ],
        ];
    }

    /**
     * Cart line key from `cart_item_key` or legacy client alias `key`.
     */
    public static function cart_item_key_from_request( WP_REST_Request $request ): string {
        $key = $request->get_param( 'cart_item_key' );
        if ( is_string( $key ) && $key !== '' ) {
            return sanitize_text_field( $key );
        }
        $key = $request->get_param( 'key' );
        return is_string( $key ) ? sanitize_text_field( $key ) : '';
    }

    /**
     * REST args for cart update/remove (accept both parameter names).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function cart_item_key_args(): array {
        return [
            'cart_item_key' => [
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'key'           => [
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Duplicate resource list under `items` for mobile clients.
     *
     * @param array<string, mixed> $payload
     */
    public static function mirror_list_items( array $payload, string $resource_key ): array {
        if ( isset( $payload[ $resource_key ] ) && is_array( $payload[ $resource_key ] ) ) {
            $payload['items'] = $payload[ $resource_key ];
        }
        return $payload;
    }
}
