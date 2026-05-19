<?php
defined( 'ABSPATH' ) || exit;

/**
 * Search Module
 *
 * GET /storefuse/v1/search?q=
 */
class StoreFuse_Bridge_Module_Search extends StoreFuse_Bridge_Module {

    protected string $id = 'search';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/search', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'search' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
            'args'                => [
                'q'        => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type'              => 'integer',
                    'default'           => 12,
                    'minimum'           => 1,
                    'maximum'           => 50,
                    'sanitize_callback' => 'absint',
                ],
                'page'     => [
                    'type'              => 'integer',
                    'default'           => 1,
                    'minimum'           => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    // ── GET /search ───────────────────────────────────────────────────────────

    public function search( WP_REST_Request $request ): WP_REST_Response {

        $q        = trim( (string) $request->get_param( 'q' ) );
        $per_page = min( 50, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $page     = max( 1, (int) $request->get_param( 'page' ) );

        $empty = [
            'items' => [],
            'meta'  => [
                'total'       => 0,
                'total_pages' => 0,
                'page'        => $page,
                'per_page'    => $per_page,
                'query'       => $q,
            ],
        ];

        if ( strlen( $q ) < 2 ) {
            return StoreFuse_Bridge_Response::success( $empty, 'storefuse.search.v1' );
        }

        $query = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            's'              => $q,
            'tax_query'      => [ [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => [ 'exclude-from-search' ],
                'operator' => 'NOT IN',
            ] ],
        ] );

        $items = [];
        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                continue;
            }
            $items[] = [
                'id'                => $product->get_id(),
                'slug'              => $product->get_slug(),
                'name'              => $product->get_name(),
                'href'              => '/product/' . $product->get_slug(),
                'price'             => StoreFuse_Bridge_Format::price( $product->get_price() ),
                'regular_price'     => StoreFuse_Bridge_Format::price( $product->get_regular_price() ),
                'sale_price'        => $product->get_sale_price() ? StoreFuse_Bridge_Format::price( $product->get_sale_price() ) : null,
                'on_sale'           => $product->is_on_sale(),
                'thumbnail'         => StoreFuse_Bridge_Format::image( $product->get_image_id() ),
                'average_rating'    => (float) $product->get_average_rating(),
                'short_description' => wp_strip_all_tags( $product->get_short_description() ),
            ];
        }

        $total = (int) $query->found_posts;
        $data  = [
            'items' => $items,
            'meta'  => [
                'total'       => $total,
                'total_pages' => (int) ceil( $total / $per_page ),
                'page'        => $page,
                'per_page'    => $per_page,
                'query'       => $q,
            ],
        ];

        $data = apply_filters( 'storefuse_bridge_search_response', $data, $request );

        return StoreFuse_Bridge_Response::success( $data, 'storefuse.search.v1' );
    }
}
