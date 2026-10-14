<?php
defined( 'ABSPATH' ) || exit;

/** Customer-initiated requests processed by WordPress's privacy tools. */
final class StoreFuse_Bridge_Privacy {
    public static function init(): void {
        add_filter( 'wp_privacy_personal_data_erasers', [ self::class, 'erasers' ], 100 );
        add_action( 'template_redirect', [ self::class, 'web_page' ] );
    }

    /** Publicly reachable deletion entry point, including after app uninstall. */
    public static function web_page(): void {
        if ( ! isset( $_GET['storefuse_account_deletion'] ) ) {
            return;
        }
        nocache_headers();
        $url = add_query_arg( 'storefuse_account_deletion', '1', home_url( '/' ) );
        $message = '';
        if ( is_user_logged_in() && ( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST' ) {
            check_admin_referer( 'storefuse_delete_account' );
            $response = self::request( new WP_REST_Request( 'POST' ) )->get_data();
            $message = $response['data']['message'] ?? $response['error']['message'] ?? 'Request received.';
        }
        header( 'Content-Type: text/html; charset=utf-8' );
        echo '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Account deletion</title></head><body><main style="max-width:40rem;margin:3rem auto;padding:1rem;font-family:system-ui">';
        echo '<h1>Request account deletion</h1><p>The store will process your request through its privacy tools. Required order records may be retained according to the store privacy policy.</p>';
        if ( $message ) {
            echo '<p role="status">' . esc_html( $message ) . '</p>';
        } elseif ( is_user_logged_in() ) {
            echo '<form method="post" action="' . esc_url( $url ) . '">';
            wp_nonce_field( 'storefuse_delete_account' );
            echo '<button type="submit">Request deletion of my customer account</button></form>';
        } else {
            echo '<p><a href="' . esc_url( wp_login_url( $url ) ) . '">Sign in to request deletion</a></p>';
        }
        if ( get_privacy_policy_url() ) {
            echo '<p><a href="' . esc_url( get_privacy_policy_url() ) . '">Privacy policy</a></p>';
        }
        echo '</main></body></html>';
        exit;
    }

    public static function request( WP_REST_Request $request ): WP_REST_Response {
        $limit = StoreFuse_Bridge_Auth::throttle( 'privacy_request', 'user:' . get_current_user_id(), true );
        if ( $limit ) {
            return $limit;
        }
        $user = wp_get_current_user();
        if ( array_diff( $user->roles, [ 'customer', 'subscriber' ] ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Contact the site administrator to remove a staff account.' );
        }
        // Authenticated, nonce-checked initiation is the customer's confirmation.
        // Execution remains an explicit action in Tools > Erase Personal Data.
        $id = wp_create_user_request( $user->user_email, 'remove_personal_data', [ 'storefuse_delete_account' => true ], 'confirmed' );
        if ( is_wp_error( $id ) ) {
            return StoreFuse_Bridge_Errors::validation_error( $id->get_error_message() );
        }
        return StoreFuse_Bridge_Response::with_no_store( StoreFuse_Bridge_Response::success( [
            'request_id' => $id,
            'status' => 'requested',
            'message' => 'Your deletion request is ready for the store administrator. Required order records may be retained under the store privacy policy.',
        ], 'storefuse.account.v1' ) );
    }

    public static function status( WP_REST_Request $request ): WP_REST_Response {
        $requests = get_posts( [
            'post_type' => 'user_request', 'post_status' => [ 'request-pending', 'request-confirmed', 'request-failed', 'request-completed' ],
            'author' => get_current_user_id(), 'name' => 'remove_personal_data',
            'numberposts' => 1, 'orderby' => 'date', 'order' => 'DESC',
        ] );
        $data = $requests ? [ 'request_id' => $requests[0]->ID, 'status' => $requests[0]->post_status ] : [ 'status' => 'none' ];
        return StoreFuse_Bridge_Response::with_no_store( StoreFuse_Bridge_Response::success( $data, 'storefuse.account.v1' ) );
    }

    public static function erasers( array $erasers ): array {
        $erasers['storefuse-account'] = [
            'eraser_friendly_name' => __( 'StoreFuse customer account', 'storefuse-bridge' ),
            'callback' => [ self::class, 'erase' ],
        ];
        return $erasers;
    }

    public static function erase( string $email, int $page = 1 ): array {
        $result = [ 'items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true ];
        $requests = get_posts( [ 'post_type' => 'user_request', 'post_status' => 'request-confirmed', 'title' => $email, 'name' => 'remove_personal_data', 'numberposts' => 10 ] );
        $authorized = false;
        foreach ( $requests as $post ) {
            $data = json_decode( $post->post_content, true );
            $authorized = $authorized || ! empty( $data['storefuse_delete_account'] );
        }
        $user = get_user_by( 'email', $email );
        if ( ! $authorized || ! $user ) {
            return $result;
        }
        if ( array_diff( $user->roles, [ 'customer', 'subscriber' ] ) || $user->ID === get_current_user_id() ) {
            $result['items_retained'] = true;
            $result['messages'][] = 'A staff account requires manual administrator review.';
            return $result;
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $result['items_removed'] = wp_delete_user( $user->ID );
        return $result;
    }
}
