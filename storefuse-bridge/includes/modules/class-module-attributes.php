<?php
defined( 'ABSPATH' ) || exit;

/**
 * Attributes Module
 *
 * Routes (public, long cache):
 *   GET /storefuse/v1/attributes  - all WC product attributes with their terms
 */
class StoreFuse_Bridge_Module_Attributes extends StoreFuse_Bridge_Module {

    protected string $id = 'attributes';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/attributes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_attributes' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handler 

    public function get_attributes( WP_REST_Request $request ): WP_REST_Response {
        $cache_key = 'attributes_all';
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    $this->success( $cached, 'storefuse.attributes.v1' ),
                    3600
                )
            );
        }

        $taxonomies = wc_get_attribute_taxonomies();
        $attributes = [];

        foreach ( $taxonomies as $taxonomy ) {
            $slug  = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
            $terms = get_terms( [
                'taxonomy'   => $slug,
                'hide_empty' => true,
            ] );

            $terms_data = [];
            if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                foreach ( $terms as $term ) {
                    $terms_data[] = [
                        'id'    => $term->term_id,
                        'name'  => $term->name,
                        'slug'  => $term->slug,
                        'count' => (int) $term->count,
                    ];
                }
            }

            $attributes[] = apply_filters( 'storefuse_bridge_attribute_data', [
                'id'       => (int) $taxonomy->attribute_id,
                'name'     => $taxonomy->attribute_label,
                'slug'     => $taxonomy->attribute_name,
                'type'     => $taxonomy->attribute_type,
                'order_by' => $taxonomy->attribute_orderby,
                'terms'    => $terms_data,
            ], $taxonomy );
        }

        $data = [ 'attributes' => $attributes ];
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            $this->success( $data, 'storefuse.attributes.v1' ),
            3600
        );
    }
}
