<?php
defined( 'ABSPATH' ) || exit;

/**
 * Auth Module
 *
 * Routes:
 *   GET  /storefuse/v1/auth/nonce         - anonymous nonce for login/register forms
 *   POST /storefuse/v1/auth/register
 *   POST /storefuse/v1/auth/login
 *   POST /storefuse/v1/auth/logout
 *   GET  /storefuse/v1/auth/me
 *   POST /storefuse/v1/auth/forgot-password
 *   POST /storefuse/v1/auth/reset-password
 *
 * All responses carry Cache-Control: no-store.
 * Login and register responses also carry X-WP-Nonce and X-StoreFuse-Cart-Token.
 *
 * Authentication strategy: WordPress native cookies (wp_signon, wp_set_auth_cookie).
 * No JWT, no localStorage tokens. HTTP-only cookies only.
 */
class StoreFuse_Bridge_Module_Auth extends StoreFuse_Bridge_Module {

    public function register_routes(): void {

        // Public nonce endpoint — called by the storefront before login/register
        // to obtain a fresh wp_rest nonce for the X-WP-Nonce header.
        register_rest_route( $this->namespace, '/auth/nonce', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_nonce' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/auth/register', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'register' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'auth_write_permission' ],
            'args'                => [
                'email'      => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'password'   => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'first_name' => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'last_name'  => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/auth/login', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'login' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'auth_write_permission' ],
            'args'                => [
                'email'    => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'password' => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'remember' => [
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => false,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/auth/logout', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'logout' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'auth_write_permission' ],
        ] );

        register_rest_route( $this->namespace, '/auth/me', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'me' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/auth/forgot-password', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'forgot_password' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'auth_write_permission' ],
            'args'                => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/auth/reset-password', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'reset_password' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'auth_write_permission' ],
            'args'                => [
                'login'    => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'key'      => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'password' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    /**
     * GET /auth/nonce
     *
     * Returns a fresh wp_rest nonce so headless storefronts can include it in
     * X-WP-Nonce on the login, register, forgot-password, and reset-password
     * requests.  Public endpoint — no auth required.
     */
    public function get_nonce( WP_REST_Request $request ): WP_REST_Response {
        $response = $this->success(
            [ 'nonce' => wp_create_nonce( 'wp_rest' ) ],
            'storefuse.auth.v1'
        );
        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * POST /auth/register
     *
     * Creates a WooCommerce customer account, auto-logs in, and returns the user
     * profile with a fresh WP nonce and cart token.
     */
    public function register( WP_REST_Request $request ): WP_REST_Response {
        $email      = $request->get_param( 'email' );
        $password   = $request->get_param( 'password' );
        $first_name = $request->get_param( 'first_name' );
        $last_name  = $request->get_param( 'last_name' );

        if ( ! is_email( $email ) ) {
            return StoreFuse_Bridge_Errors::invalid_email();
        }

        if ( email_exists( $email ) ) {
            return StoreFuse_Bridge_Errors::email_already_registered();
        }

        $user_id = wc_create_new_customer( $email, '', $password, [
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ] );

        if ( is_wp_error( $user_id ) ) {
            return StoreFuse_Bridge_Errors::validation_error( $user_id->get_error_message() );
        }

        // Auto-login after registration
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, false );

        $user = get_user_by( 'id', $user_id );
        do_action( 'wp_login', $user->user_login, $user );

        return $this->auth_success_response( $user );
    }

    /**
     * POST /auth/login
     *
     * Authenticates via wp_signon (supports 2FA plugins), sets the auth cookie,
     * and returns the user profile with nonce and cart token.
     */
    public function login( WP_REST_Request $request ): WP_REST_Response {
        $email    = $request->get_param( 'email' );
        $password = $request->get_param( 'password' );
        $remember = (bool) $request->get_param( 'remember' );

        if ( ! is_email( $email ) ) {
            return StoreFuse_Bridge_Errors::invalid_email();
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Return invalid_credentials (not user_not_found) to prevent email enumeration
            return StoreFuse_Bridge_Errors::invalid_credentials();
        }

        $result = wp_signon(
            [
                'user_login'    => $user->user_login,
                'user_password' => $password,
                'remember'      => $remember,
            ],
            is_ssl()
        );

        if ( is_wp_error( $result ) ) {
            return StoreFuse_Bridge_Errors::invalid_credentials();
        }

        wp_set_current_user( $result->ID );

        return $this->auth_success_response( $result );
    }

    /**
     * POST /auth/logout
     *
     * Terminates the WordPress session and clears auth cookie.
     * Requires the user to be logged in.
     */
    public function logout( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        wp_logout();

        $response = $this->success( [ 'logged_out' => true ], 'storefuse.auth.v1' );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * GET /auth/me
     *
     * Returns the current user profile including billing/shipping addresses
     * and a fresh nonce pair. Requires login.
     */
    public function me( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $user = wp_get_current_user();

        $response = $this->success( $this->format_user( $user ), 'storefuse.auth.v1' );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * POST /auth/forgot-password
     *
     * Dispatches a password reset email if the account exists.
     * Always returns success to prevent email enumeration.
     */
    public function forgot_password( WP_REST_Request $request ): WP_REST_Response {
        $email = $request->get_param( 'email' );

        if ( ! is_email( $email ) ) {
            return StoreFuse_Bridge_Errors::invalid_email();
        }

        // Only send if account exists - but always return success to prevent enumeration
        $user = get_user_by( 'email', $email );
        if ( $user ) {
            $key = get_password_reset_key( $user );
            if ( ! is_wp_error( $key ) ) {
                $this->send_password_reset_email( $user, $key );
            }
        }

        $response = $this->success( [ 'email_sent' => true ], 'storefuse.auth.v1' );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * POST /auth/reset-password
     *
     * Validates the reset key and sets the new password.
     * The frontend obtains `login` and `key` from the reset URL query params.
     */
    public function reset_password( WP_REST_Request $request ): WP_REST_Response {
        $login    = $request->get_param( 'login' );
        $key      = $request->get_param( 'key' );
        $password = $request->get_param( 'password' );

        $user = check_password_reset_key( $key, $login );

        if ( is_wp_error( $user ) ) {
            return StoreFuse_Bridge_Errors::validation_error(
                'Password reset link is invalid or has expired.'
            );
        }

        reset_password( $user, $password );

        $response = $this->success( [ 'password_reset' => true ], 'storefuse.auth.v1' );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    // ── Helpers 

    /**
     * Build the standard auth success response with required headers.
     *
     * X-WP-Nonce      - fresh nonce for subsequent authenticated REST calls
     * X-StoreFuse-Cart-Token - WC session ID for stateless cart clients
     */
    private function auth_success_response( WP_User $user ): WP_REST_Response {
        $response = $this->success( $this->format_user( $user ), 'storefuse.auth.v1' );

        $response->header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
        $response = StoreFuse_Bridge_Session::set_cart_token_header( $response );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * Normalise a WP_User to the StoreFuse user data shape.
     *
     * nonce      - use in X-WP-Nonce for standard REST write endpoints
     * cart_nonce - use in X-WC-Nonce for cart/checkout endpoints
     */
    private function format_user( WP_User $user ): array {
        $customer = new WC_Customer( $user->ID );

        return apply_filters(
            'storefuse_bridge_user_data',
            [
                'id'           => $user->ID,
                'email'        => $user->user_email,
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'display_name' => $user->display_name,
                'avatar_url'   => get_avatar_url( $user->ID, [ 'size' => 96 ] ),
                'billing'      => $this->format_address( $customer->get_billing() ),
                'shipping'     => $this->format_address( $customer->get_shipping() ),
                'nonce'        => wp_create_nonce( 'wp_rest' ),
                'cart_nonce'   => wp_create_nonce( 'wc_store_api' ),
            ],
            $user
        );
    }

    /**
     * Normalise a WooCommerce address array into the StoreFuse address shape.
     */
    private function format_address( array $address ): array {
        return [
            'first_name' => $address['first_name'] ?? '',
            'last_name'  => $address['last_name']  ?? '',
            'company'    => $address['company']    ?? '',
            'address_1'  => $address['address_1']  ?? '',
            'address_2'  => $address['address_2']  ?? '',
            'city'       => $address['city']       ?? '',
            'state'      => $address['state']      ?? '',
            'postcode'   => $address['postcode']   ?? '',
            'country'    => $address['country']    ?? '',
            'phone'      => $address['phone']      ?? '',
            'email'      => $address['email']      ?? '',
        ];
    }

    /**
     * Send the password reset email with a link to the WP login reset page.
     *
     * The storefront can override the reset URL via the
     * `storefuse_bridge_password_reset_url` filter to point to its own
     * /reset-password page instead of the WP login page.
     */
    private function send_password_reset_email( WP_User $user, string $key ): void {
        $reset_url = add_query_arg(
            [
                'action' => 'rp',
                'key'    => rawurlencode( $key ),
                'login'  => rawurlencode( $user->user_login ),
            ],
            wp_login_url()
        );

        /**
         * Filters the password reset URL.
         *
         * Use this filter in your theme or plugin to point to the storefront
         * reset password page instead of the WordPress login page.
         *
         * @param string  $reset_url Default WordPress reset URL.
         * @param WP_User $user      The user requesting the reset.
         * @param string  $key       The raw password reset key.
         */
        $reset_url = apply_filters( 'storefuse_bridge_password_reset_url', $reset_url, $user, $key );

        $site_name = get_bloginfo( 'name' );

        $subject = sprintf( 'Password reset request for %s', $site_name );

        $message  = sprintf( "Someone requested a password reset for the account associated with this email address on %s.\n\n", $site_name );
        $message .= "If this was a mistake, you can ignore this email.\n\n";
        $message .= "To reset your password, visit the link below:\n";
        $message .= $reset_url . "\n\n";
        $message .= "This link will expire in 24 hours.";

        wp_mail( $user->user_email, $subject, $message );
    }
}
