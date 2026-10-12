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
}
