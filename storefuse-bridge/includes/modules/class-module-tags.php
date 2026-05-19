<?php
defined( 'ABSPATH' ) || exit;

/**
 * Tags Module
 *
 * Routes (public, long cache):
 *   GET /storefuse/v1/tags  - product tags ordered by usage count
 */
class StoreFuse_Bridge_Module_Tags extends StoreFuse_Bridge_Module {

    protected string $id = 'tags';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/tags', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_tags' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handler 

    public function get_tags( WP_REST_Request $request ): WP_REST_Response {
        $cache_key = 'product_tags';
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    $this->success( $cached, 'storefuse.tags.v1' ),
                    3600
                )
            );
        }

        $terms = get_terms( [
            'taxonomy'   => 'product_tag',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 200,
        ] );

        $tags = [];
        if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
            foreach ( $terms as $term ) {
                $tags[] = apply_filters( 'storefuse_bridge_tag_data', [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => (int) $term->count,
                ], $term );
            }
        }

        $data = [ 'tags' => $tags ];
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            $this->success( $data, 'storefuse.tags.v1' ),
            3600
        );
    }
}
