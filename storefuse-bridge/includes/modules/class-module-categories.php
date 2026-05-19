<?php
defined( 'ABSPATH' ) || exit;

/**
 * Categories Module
 *
 * GET /storefuse/v1/categories
 * GET /storefuse/v1/categories/{slug}
 */
class StoreFuse_Bridge_Module_Categories extends StoreFuse_Bridge_Module {

    protected string $id = 'categories';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/categories', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_categories' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
        ] );

        register_rest_route( $this->namespace, '/categories/(?P<slug>[a-z0-9_-]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_category' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ] );
    }

    // ── GET /categories ───────────────────────────────────────────────────────

    public function get_categories( WP_REST_Request $request ): WP_REST_Response {

        $cache_key = StoreFuse_Bridge_Cache::categories_key();
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    StoreFuse_Bridge_Response::success( $cached, 'storefuse.categories.v1' ),
                    3600
                )
            );
        }

        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
            'exclude'    => [ $this->uncategorized_id() ],
        ] );

        if ( is_wp_error( $terms ) ) {
            return StoreFuse_Bridge_Errors::server_error( $terms->get_error_message() );
        }

        // Build flat map and parent lookup in one pass
        $flat       = [];
        $parent_map = [];

        foreach ( $terms as $term ) {
            $flat[ $term->term_id ]       = StoreFuse_Bridge_Format::category( $term );
            $flat[ $term->term_id ]['children'] = [];
            $parent_map[ $term->term_id ] = (int) $term->parent;
        }

        // Build tree using references
        $tree = [];
        foreach ( $flat as $id => &$item ) {
            $parent_id = $parent_map[ $id ] ?? 0;
            if ( $parent_id === 0 || ! isset( $flat[ $parent_id ] ) ) {
                $tree[] = &$item;
            } else {
                $flat[ $parent_id ]['children'][] = &$item;
            }
        }
        unset( $item );

        $data = [
            'items' => array_values( $tree ),
            'total' => count( $flat ),
        ];

        $data = apply_filters( 'storefuse_bridge_categories_response', $data, $request );
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            StoreFuse_Bridge_Response::success( $data, 'storefuse.categories.v1' ),
            3600
        );
    }

    // ── GET /categories/{slug} ────────────────────────────────────────────────

    public function get_category( WP_REST_Request $request ): WP_REST_Response {

        $slug      = $request->get_param( 'slug' );
        $cache_key = StoreFuse_Bridge_Cache::category_key( $slug );
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    StoreFuse_Bridge_Response::success( $cached, 'storefuse.category.v1' ),
                    3600
                )
            );
        }

        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term ) {
            return StoreFuse_Bridge_Errors::category_not_found();
        }

        $data = StoreFuse_Bridge_Format::category( $term );

        // Direct children
        $children       = get_terms( [
            'taxonomy'   => 'product_cat',
            'parent'     => $term->term_id,
            'hide_empty' => false,
        ] );
        $data['children'] = is_wp_error( $children ) ? [] : array_values(
            array_map( [ StoreFuse_Bridge_Format::class, 'category' ], $children )
        );

        // Breadcrumb
        $data['breadcrumb'] = $this->build_category_breadcrumb( $term );

        $data = apply_filters( 'storefuse_bridge_category_response', $data, $term, $request );
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 3600 );

        return StoreFuse_Bridge_Response::with_public_cache(
            StoreFuse_Bridge_Response::success( $data, 'storefuse.category.v1' ),
            3600
        );
    }

    // ── Helpers ─

    private function build_category_breadcrumb( WP_Term $term ): array {
        $ancestors = [];
        $current   = $term;

        while ( $current->parent ) {
            $parent = get_term( (int) $current->parent, 'product_cat' );
            if ( is_wp_error( $parent ) || ! $parent ) {
                break;
            }
            $ancestors[] = $parent;
            $current     = $parent;
        }

        $crumbs   = [
            [ 'label' => __( 'Home', 'storefuse-bridge' ), 'href' => '/' ],
            [ 'label' => __( 'Shop', 'storefuse-bridge' ), 'href' => '/shop' ],
        ];
        foreach ( array_reverse( $ancestors ) as $anc ) {
            $crumbs[] = [ 'label' => $anc->name, 'href' => '/category/' . $anc->slug ];
        }
        $crumbs[] = [ 'label' => $term->name, 'href' => '/category/' . $term->slug ];

        return $crumbs;
    }

    private function uncategorized_id(): int {
        $term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
        return $term ? (int) $term->term_id : 0;
    }
}
