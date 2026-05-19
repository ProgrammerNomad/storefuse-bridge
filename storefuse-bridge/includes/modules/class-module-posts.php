<?php
defined( 'ABSPATH' ) || exit;

/**
 * Posts Module
 *
 * Routes (public, cached):
 *   GET /storefuse/v1/posts               - paginated post list with excerpts + featured image
 *   GET /storefuse/v1/posts/{slug}        - single post with full content
 */
class StoreFuse_Bridge_Module_Posts extends StoreFuse_Bridge_Module {

    protected string $id = 'posts';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/posts', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_posts' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'page'     => [ 'required' => false, 'type' => 'integer', 'default' => 1,  'minimum' => 1 ],
                'per_page' => [ 'required' => false, 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50 ],
                'category' => [ 'required' => false, 'type' => 'string',  'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'tag'      => [ 'required' => false, 'type' => 'string',  'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/posts/(?P<slug>[a-zA-Z0-9_-]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_post' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_posts( WP_REST_Request $request ): WP_REST_Response {
        $page     = (int) $request->get_param( 'page' );
        $per_page = (int) $request->get_param( 'per_page' );
        $category = $request->get_param( 'category' );
        $tag      = $request->get_param( 'tag' );

        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( $category ) {
            $args['category_name'] = $category;
        }
        if ( $tag ) {
            $args['tag'] = $tag;
        }

        $query = new WP_Query( $args );

        $posts = array_map( [ $this, 'format_summary' ], $query->posts );

        $response = $this->success(
            [
                'posts' => $posts,
                'meta'  => [
                    'total'       => (int) $query->found_posts,
                    'page'        => $page,
                    'per_page'    => $per_page,
                    'total_pages' => (int) $query->max_num_pages,
                ],
            ],
            'storefuse.posts.v1'
        );

        return StoreFuse_Bridge_Response::with_public_cache( $response, 300 );
    }

    public function get_post( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_title( $request->get_param( 'slug' ) );
        $post = get_page_by_path( $slug, OBJECT, 'post' );

        if ( ! $post || $post->post_status !== 'publish' ) {
            return new WP_REST_Response(
                [ 'code' => 'post_not_found', 'message' => 'Post not found.', 'status' => 404 ],
                404
            );
        }

        $response = $this->success( $this->format_full( $post ), 'storefuse.post.v1' );
        return StoreFuse_Bridge_Response::with_public_cache( $response, 300 );
    }

    // ── Helpers 

    private function format_summary( WP_Post $post ): array {
        $thumbnail_id = (int) get_post_thumbnail_id( $post->ID );
        $author       = get_userdata( $post->post_author );
        $categories   = get_the_terms( $post->ID, 'category' );

        return apply_filters( 'storefuse_bridge_post_summary', [
            'id'             => $post->ID,
            'slug'           => $post->post_name,
            'title'          => get_the_title( $post ),
            'excerpt'        => wp_trim_words(
                wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ),
                30
            ),
            'date'           => StoreFuse_Bridge_Format::date( $post->post_date ),
            'featured_image' => $thumbnail_id ? StoreFuse_Bridge_Format::image( $thumbnail_id ) : null,
            'author'         => $author ? $author->display_name : '',
            'categories'     => is_array( $categories )
                ? array_map(
                    static fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ],
                    $categories
                )
                : [],
            'reading_time'   => $this->reading_time( $post->post_content ),
        ], $post );
    }

    private function format_full( WP_Post $post ): array {
        $summary = $this->format_summary( $post );
        $tags    = get_the_terms( $post->ID, 'post_tag' );

        return array_merge( $summary, apply_filters( 'storefuse_bridge_post_extras', [
            'content' => apply_filters( 'the_content', $post->post_content ),
            'tags'    => is_array( $tags )
                ? array_map(
                    static fn( $t ) => [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ],
                    $tags
                )
                : [],
            'seo'     => apply_filters( 'storefuse_bridge_post_seo', [
                'title'       => get_the_title( $post ),
                'description' => wp_trim_words(
                    wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ),
                    20
                ),
            ], $post ),
        ], $post ) );
    }

    /** Estimated reading time in minutes (200 wpm average). */
    private function reading_time( string $content ): int {
        $words = str_word_count( wp_strip_all_tags( $content ) );
        return (int) max( 1, ceil( $words / 200 ) );
    }
}
