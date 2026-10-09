<?php
defined( 'ABSPATH' ) || exit;

/**
 * Products Module
 *
 * GET  /storefuse/v1/products
 * GET  /storefuse/v1/products/{slug}
 * POST /storefuse/v1/products/{slug}/notify
 */
class StoreFuse_Bridge_Module_Products extends StoreFuse_Bridge_Module {

    protected string $id = 'products';

    /** @var int[]|null */
    private static ?array $on_sale_ids_cache = null;

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/products', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_products' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
            'args'                => $this->collection_params(),
        ] );

        register_rest_route( $this->namespace, '/products/(?P<slug>[a-z0-9_-]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_product' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/products/(?P<slug>[a-z0-9_-]+)/notify', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'notify_signup' ],
            'permission_callback' => [ StoreFuse_Bridge_Auth::class, 'public_permission' ],
            'args'                => [
                'slug'  => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email',
                ],
                'consent' => [
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => false,
                ],
                'website' => [
                    'required' => false,
                    'type'     => 'string',
                    'default'  => '',
                ],
            ],
        ] );
    }

    // ── GET /products ─────────────────────────────────────────────────────────

    public function get_products( WP_REST_Request $request ): WP_REST_Response {

        $params    = $request->get_params();
        $per_page  = min( 100, max( 1, (int) ( $params['per_page'] ?? 12 ) ) );
        $page      = max( 1, (int) ( $params['page'] ?? 1 ) );

        $cache_key = StoreFuse_Bridge_Cache::products_list_key( $params );
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    StoreFuse_Bridge_Response::success( $cached, 'storefuse.products.v1' ),
                    300
                )
            );
        }

        $query = new WP_Query( $this->build_query_args( $params, $per_page, $page ) );
        $total = (int) $query->found_posts;
        $items = [];

        $post_ids = wp_list_pluck( $query->posts, 'ID' );
        $by_id    = [];
        if ( $post_ids ) {
            $products = wc_get_products( [
                'include' => array_map( 'intval', $post_ids ),
                'limit'   => count( $post_ids ),
                'status'  => 'publish',
            ] );
            foreach ( $products as $product ) {
                $by_id[ $product->get_id() ] = $product;
            }
        }

        foreach ( $post_ids as $post_id ) {
            $product = $by_id[ (int) $post_id ] ?? null;
            if ( $product ) {
                $items[] = StoreFuse_Bridge_Format::product( $product );
            }
        }

        $data = [
            'items' => $items,
            'meta'  => [
                'total'       => $total,
                'total_pages' => (int) ceil( $total / $per_page ),
                'page'        => $page,
                'per_page'    => $per_page,
            ],
        ];

        $data = apply_filters( 'storefuse_bridge_products_response', $data, $request );
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 300 );

        return StoreFuse_Bridge_Response::with_public_cache(
            StoreFuse_Bridge_Response::success( $data, 'storefuse.products.v1' ),
            300
        );
    }

    // ── GET /products/{slug} ──────────────────────────────────────────────────

    public function get_product( WP_REST_Request $request ): WP_REST_Response {

        $slug      = $request->get_param( 'slug' );
        $cache_key = StoreFuse_Bridge_Cache::product_key( $slug );
        $cached    = StoreFuse_Bridge_Cache::get( $cache_key );

        if ( $cached !== null ) {
            return StoreFuse_Bridge_Response::mark_cache_hit(
                StoreFuse_Bridge_Response::with_public_cache(
                    StoreFuse_Bridge_Response::success( $cached, 'storefuse.product.v1' ),
                    300
                )
            );
        }

        $post = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $post ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $product = wc_get_product( $post->ID );
        if ( ! $product || $product->get_status() !== 'publish' ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $data = StoreFuse_Bridge_Format::product( $product );

        // Related products (up to 6, compact shape)
        $related_ids = array_slice( wc_get_related_products( $product->get_id(), 6 ), 0, 6 );
        $related     = [];
        foreach ( $related_ids as $id ) {
            $rel = wc_get_product( $id );
            if ( $rel && $rel->get_status() === 'publish' ) {
                $related[] = $this->product_summary( $rel );
            }
        }
        $data['related_products'] = $related;

        // Breadcrumb
        $data['breadcrumb'] = $this->build_product_breadcrumb( $product );

        $data = apply_filters( 'storefuse_bridge_product_response', $data, $product, $request );
        StoreFuse_Bridge_Cache::set( $cache_key, $data, 300 );

        return StoreFuse_Bridge_Response::with_public_cache(
            StoreFuse_Bridge_Response::success( $data, 'storefuse.product.v1' ),
            300
        );
    }

    // ── POST /products/{slug}/notify ──────────────────────────────────────────

    public function notify_signup( WP_REST_Request $request ): WP_REST_Response {

        if ( $request->get_param( 'website' ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Invalid request.' );
        }

        if ( ! $request->get_param( 'consent' ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Consent is required to receive notifications.' );
        }

        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
        $throttle = StoreFuse_Bridge_Auth::throttle( 'notify_signup', $ip . ':' . sanitize_email( (string) $request->get_param( 'email' ) ) );
        if ( $throttle ) {
            return $throttle;
        }

        $slug  = $request->get_param( 'slug' );
        $email = $request->get_param( 'email' );

        $post = get_page_by_path( $slug, OBJECT, 'product' );
        if ( ! $post ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        // Store signup against product ID (append-only, deduplicated)
        $option_key = 'sfb_notify_' . (int) $product->get_id();
        $signups    = get_option( $option_key, [] );
        if ( ! is_array( $signups ) ) {
            $signups = [];
        }
        $already = false;
        foreach ( $signups as $entry ) {
            $existing = is_array( $entry ) ? ( $entry['email'] ?? '' ) : (string) $entry;
            if ( strtolower( $existing ) === strtolower( $email ) ) {
                $already = true;
                break;
            }
        }

        if ( ! $already ) {
            $signups[] = [
                'email'   => $email,
                'consent' => true,
                'time'    => current_time( 'mysql' ),
            ];
            update_option( $option_key, $signups, false );
        }

        // Allow third-party plugins (e.g. WC Waitlist) to hook in
        do_action( 'storefuse_bridge_notify_signup', $email, $product );

        return StoreFuse_Bridge_Response::success(
            [ 'message' => __( 'You will be notified when this product is back in stock.', 'storefuse-bridge' ) ],
            'storefuse.notify.v1'
        );
    }

    // ── Helpers ─

    private function build_query_args( array $params, int $per_page, int $page ): array {

        // Orderby mapping
        $orderby_map = [
            'date'       => 'date',
            'title'      => 'title',
            'price'      => 'meta_value_num',
            'popularity' => 'meta_value_num',
            'rating'     => 'meta_value_num',
            'menu_order' => 'menu_order',
        ];

        $orderby_raw = sanitize_key( $params['orderby'] ?? 'date' );
        $orderby     = $orderby_map[ $orderby_raw ] ?? 'date';
        $order       = strtoupper( sanitize_key( $params['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => $orderby,
            'order'          => $order,
            'tax_query'      => [ 'relation' => 'AND' ],
            'meta_query'     => [ 'relation' => 'AND' ],
        ];

        // Meta key for sort
        if ( $orderby_raw === 'price' ) {
            $args['meta_key'] = '_price';
        } elseif ( $orderby_raw === 'popularity' ) {
            $args['meta_key'] = 'total_sales';
        } elseif ( $orderby_raw === 'rating' ) {
            $args['meta_key'] = '_wc_average_rating';
        }

        // Text search
        if ( ! empty( $params['search'] ) ) {
            $args['s'] = sanitize_text_field( $params['search'] );
        }

        // Category
        if ( ! empty( $params['category'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_title( $params['category'] ),
            ];
        }

        // Tag
        if ( ! empty( $params['tag'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => sanitize_title( $params['tag'] ),
            ];
        }

        // On sale
        if ( ! empty( $params['on_sale'] ) ) {
            if ( self::$on_sale_ids_cache === null ) {
                self::$on_sale_ids_cache = wc_get_product_ids_on_sale();
            }
            $args['post__in'] = ! empty( self::$on_sale_ids_cache ) ? self::$on_sale_ids_cache : [ 0 ];
        }

        // Featured
        if ( ! empty( $params['featured'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ];
        }

        // Price range
        if ( ! empty( $params['min_price'] ) ) {
            $args['meta_query'][] = [
                'key'     => '_price',
                'value'   => (float) $params['min_price'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ];
        }
        if ( ! empty( $params['max_price'] ) ) {
            $args['meta_query'][] = [
                'key'     => '_price',
                'value'   => (float) $params['max_price'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
        }

        // Attribute filters: ?pa_color=red,blue  or  ?attribute_pa_color=red
        foreach ( $params as $key => $val ) {
            $key = sanitize_key( $key );
            if ( strpos( $key, 'pa_' ) === 0 || strpos( $key, 'attribute_pa_' ) === 0 ) {
                $taxonomy            = ( strpos( $key, 'attribute_' ) === 0 ) ? substr( $key, 10 ) : $key;
                $terms               = array_map( 'sanitize_title', explode( ',', (string) $val ) );
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $terms,
                ];
            }
        }

        // Exclude hidden from catalog
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => [ 'exclude-from-catalog' ],
            'operator' => 'NOT IN',
        ];

        return $args;
    }

    private function product_summary( WC_Product $product ): array {
        return [
            'id'             => $product->get_id(),
            'slug'           => $product->get_slug(),
            'name'           => $product->get_name(),
            'href'           => '/product/' . $product->get_slug(),
            'price'          => StoreFuse_Bridge_Format::price( $product->get_price() ),
            'regular_price'  => StoreFuse_Bridge_Format::price( $product->get_regular_price() ),
            'sale_price'     => $product->get_sale_price() ? StoreFuse_Bridge_Format::price( $product->get_sale_price() ) : null,
            'on_sale'        => $product->is_on_sale(),
            'thumbnail'      => StoreFuse_Bridge_Format::image( $product->get_image_id() ),
            'average_rating' => (float) $product->get_average_rating(),
            'review_count'   => $product->get_review_count(),
        ];
    }

    private function build_product_breadcrumb( WC_Product $product ): array {
        $crumbs = [
            [ 'label' => __( 'Home', 'storefuse-bridge' ), 'href' => '/' ],
            [ 'label' => __( 'Shop', 'storefuse-bridge' ), 'href' => '/shop' ],
        ];

        $terms = get_the_terms( $product->get_id(), 'product_cat' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            // Prefer deepest (most specific) category: highest parent count
            usort( $terms, fn( $a, $b ) => $b->parent <=> $a->parent );
            $term     = $terms[0];
            $crumbs[] = [ 'label' => $term->name, 'href' => '/category/' . $term->slug ];
        }

        $crumbs[] = [ 'label' => $product->get_name(), 'href' => '/product/' . $product->get_slug() ];

        return $crumbs;
    }

    private function collection_params(): array {
        return [
            'per_page'  => [ 'type' => 'integer', 'default' => 12, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint' ],
            'page'      => [ 'type' => 'integer', 'default' => 1,  'minimum' => 1, 'sanitize_callback' => 'absint' ],
            'category'  => [ 'type' => 'string',  'sanitize_callback' => 'sanitize_title' ],
            'tag'       => [ 'type' => 'string',  'sanitize_callback' => 'sanitize_title' ],
            'orderby'   => [ 'type' => 'string',  'default' => 'date',  'sanitize_callback' => 'sanitize_key' ],
            'order'     => [ 'type' => 'string',  'default' => 'desc',  'sanitize_callback' => 'sanitize_key' ],
            'on_sale'   => [ 'type' => 'boolean', 'default' => false ],
            'featured'  => [ 'type' => 'boolean', 'default' => false ],
            'min_price' => [ 'type' => 'number',  'sanitize_callback' => 'floatval' ],
            'max_price' => [ 'type' => 'number',  'sanitize_callback' => 'floatval' ],
            'search'    => [ 'type' => 'string',  'sanitize_callback' => 'sanitize_text_field' ],
        ];
    }
}
