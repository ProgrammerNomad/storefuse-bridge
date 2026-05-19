<?php
defined( 'ABSPATH' ) || exit;

/**
 * Reviews Module
 *
 * WooCommerce product reviews are stored as WordPress comments (comment_type = 'review').
 * Rating is stored in comment meta under key 'rating'.
 *
 * Routes:
 *   GET  /storefuse/v1/reviews?product_id=  - paginated reviews (public, short cache)
 *   POST /storefuse/v1/reviews              - submit a review (requires login + X-WP-Nonce)
 */
class StoreFuse_Bridge_Module_Reviews extends StoreFuse_Bridge_Module {

    protected string $id = 'reviews';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/reviews', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_reviews' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'product_id' => [ 'required' => false, 'type' => 'integer', 'default' => 0 ],
                    'page'       => [ 'required' => false, 'type' => 'integer', 'default' => 1, 'minimum' => 1 ],
                    'per_page'   => [ 'required' => false, 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50 ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_review' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'product_id' => [ 'required' => true,  'type' => 'integer', 'minimum' => 1 ],
                    'rating'     => [ 'required' => true,  'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ],
                    'content'    => [ 'required' => true,  'type' => 'string' ],
                    'title'      => [ 'required' => false, 'type' => 'string', 'default' => '' ],
                ],
            ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_reviews( WP_REST_Request $request ): WP_REST_Response {
        $product_id = (int) $request->get_param( 'product_id' );
        $page       = (int) $request->get_param( 'page' );
        $per_page   = (int) $request->get_param( 'per_page' );

        if ( $product_id && ! wc_get_product( $product_id ) ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $base_args = [
            'status'    => 'approve',
            'type'      => 'review',
            'orderby'   => 'comment_date',
            'order'     => 'DESC',
            'post_type' => 'product',
        ];

        if ( $product_id ) {
            $base_args['post_id'] = $product_id;
        }

        $reviews = get_comments( array_merge( $base_args, [
            'number' => $per_page,
            'offset' => ( $page - 1 ) * $per_page,
        ] ) );

        $total = (int) get_comments( array_merge( $base_args, [
            'count'  => true,
            'number' => 0,
            'offset' => 0,
        ] ) );

        $data          = array_map( [ $this, 'format_review' ], $reviews );
        $rating_stats  = $product_id ? $this->rating_stats( $product_id ) : null;

        $response = $this->success(
            [
                'reviews'      => $data,
                'rating_stats' => $rating_stats,
                'meta'         => [
                    'total'       => $total,
                    'page'        => $page,
                    'per_page'    => $per_page,
                    'total_pages' => (int) ceil( $total / $per_page ),
                ],
            ],
            'storefuse.reviews.v1'
        );

        return StoreFuse_Bridge_Response::with_public_cache( $response, 300 );
    }

    public function create_review( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $product_id = (int) $request->get_param( 'product_id' );
        $product    = wc_get_product( $product_id );

        if ( ! $product ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $user    = wp_get_current_user();
        $rating  = (int) $request->get_param( 'rating' );
        $content = wp_kses_post( $request->get_param( 'content' ) );
        $title   = sanitize_text_field( $request->get_param( 'title' ) ?? '' );

        // Prevent duplicate reviews from the same user
        $existing = (int) get_comments( [
            'user_id' => $user->ID,
            'post_id' => $product_id,
            'count'   => true,
        ] );

        if ( $existing > 0 ) {
            return StoreFuse_Bridge_Errors::validation_error( 'You have already reviewed this product.' );
        }

        $review_id = wp_insert_comment( [
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_approved'     => 1,
            'user_id'              => $user->ID,
        ] );

        if ( ! $review_id || is_wp_error( $review_id ) ) {
            return StoreFuse_Bridge_Errors::server_error( 'Failed to save review.' );
        }

        update_comment_meta( $review_id, 'rating', $rating );
        update_comment_meta( $review_id, 'verified', wc_customer_bought_product( $user->user_email, $user->ID, $product_id ) ? 1 : 0 );

        if ( $title ) {
            update_comment_meta( $review_id, 'review_title', $title );
        }

        // Invalidate WC product rating cache
        delete_transient( 'wc_average_rating_' . $product_id );

        $review = get_comment( $review_id );

        return $this->success( $this->format_review( $review ), 'storefuse.reviews.v1', 201 );
    }

    // ── Helpers 

    private function check_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    private function format_review( WP_Comment $comment ): array {
        $rating   = (int)  get_comment_meta( $comment->comment_ID, 'rating',       true );
        $verified = (bool) get_comment_meta( $comment->comment_ID, 'verified',     true );
        $title    = (string) get_comment_meta( $comment->comment_ID, 'review_title', true );

        return apply_filters( 'storefuse_bridge_review_data', [
            'id'          => (int) $comment->comment_ID,
            'product_id'  => (int) $comment->comment_post_ID,
            'author'      => $comment->comment_author,
            'avatar_url'  => get_avatar_url( $comment->comment_author_email, [ 'size' => 64 ] ),
            'rating'      => $rating,
            'title'       => $title,
            'content'     => wp_strip_all_tags( $comment->comment_content ),
            'date'        => StoreFuse_Bridge_Format::date( $comment->comment_date ),
            'is_verified' => $verified,
        ], $comment );
    }

    private function rating_stats( int $product_id ): array {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return [];
        }

        $breakdown = [];
        for ( $star = 5; $star >= 1; $star-- ) {
            $breakdown[ $star ] = (int) get_comments( [
                'post_id'    => $product_id,
                'status'     => 'approve',
                'type'       => 'review',
                'count'      => true,
                'meta_query' => [ [ 'key' => 'rating', 'value' => $star, 'compare' => '=', 'type' => 'NUMERIC' ] ],
            ] );
        }

        return [
            'average'   => (float) $product->get_average_rating(),
            'total'     => (int) $product->get_review_count(),
            'breakdown' => $breakdown,
        ];
    }
}
