<?php
defined( 'ABSPATH' ) || exit;

/**
 * Account Module
 *
 * Routes:
 *   GET  /storefuse/v1/account              - get current user profile
 *   PUT  /storefuse/v1/account              - update display name / first+last name
 *   POST /storefuse/v1/account/change-password
 *
 * All routes require login (checked in handlers, not permission_callback).
 * Write routes also require a valid X-WP-Nonce header.
 */
class StoreFuse_Bridge_Module_Account extends StoreFuse_Bridge_Module {

    protected string $id = 'account';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/account', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_account' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_account' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'first_name'   => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                    'last_name'    => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                    'display_name' => [ 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/account/change-password', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'change_password' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'current_password' => [ 'required' => true, 'type' => 'string' ],
                'new_password'     => [ 'required' => true, 'type' => 'string' ],
            ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_account( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $user     = wp_get_current_user();
        $customer = new WC_Customer( $user->ID );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( $this->format_user( $user, $customer ), 'storefuse.account.v1' )
        );
    }

    public function update_account( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $user_id   = get_current_user_id();
        $user_data = [ 'ID' => $user_id ];

        $first_name   = $request->get_param( 'first_name' );
        $last_name    = $request->get_param( 'last_name' );
        $display_name = $request->get_param( 'display_name' );

        if ( $first_name !== null ) {
            $user_data['first_name'] = $first_name;
        }
        if ( $last_name !== null ) {
            $user_data['last_name'] = $last_name;
        }
        if ( $display_name !== null ) {
            $user_data['display_name'] = $display_name;
        }

        $result = wp_update_user( $user_data );
        if ( is_wp_error( $result ) ) {
            return StoreFuse_Bridge_Errors::server_error( $result->get_error_message() );
        }

        $user     = get_userdata( $user_id );
        $customer = new WC_Customer( $user_id );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( $this->format_user( $user, $customer ), 'storefuse.account.v1' )
        );
    }

    public function change_password( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $user             = wp_get_current_user();
        $current_password = $request->get_param( 'current_password' );
        $new_password     = $request->get_param( 'new_password' );

        if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
            return StoreFuse_Bridge_Errors::invalid_credentials();
        }

        wp_set_password( $new_password, $user->ID );

        // Re-authenticate so the current session remains valid after the password change.
        wp_set_auth_cookie( $user->ID, true );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( [ 'success' => true ], 'storefuse.account.v1' )
        );
    }

    // ── Helpers 

    private function check_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    private function format_user( WP_User $user, WC_Customer $customer ): array {
        return apply_filters( 'storefuse_bridge_account_data', [
            'id'           => $user->ID,
            'email'        => $user->user_email,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'display_name' => $user->display_name,
            'avatar_url'   => get_avatar_url( $user->ID, [ 'size' => 96 ] ),
            'billing'      => $this->format_customer_address( $customer, 'billing' ),
            'shipping'     => $this->format_customer_address( $customer, 'shipping' ),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'cart_nonce'   => wp_create_nonce( 'wc_store_api' ),
        ], $user );
    }

    private function format_customer_address( WC_Customer $customer, string $type ): array {
        if ( $type === 'billing' ) {
            return [
                'first_name' => $customer->get_billing_first_name(),
                'last_name'  => $customer->get_billing_last_name(),
                'company'    => $customer->get_billing_company(),
                'address_1'  => $customer->get_billing_address_1(),
                'address_2'  => $customer->get_billing_address_2(),
                'city'       => $customer->get_billing_city(),
                'state'      => $customer->get_billing_state(),
                'postcode'   => $customer->get_billing_postcode(),
                'country'    => $customer->get_billing_country(),
                'email'      => $customer->get_billing_email(),
                'phone'      => $customer->get_billing_phone(),
            ];
        }
        return [
            'first_name' => $customer->get_shipping_first_name(),
            'last_name'  => $customer->get_shipping_last_name(),
            'company'    => $customer->get_shipping_company(),
            'address_1'  => $customer->get_shipping_address_1(),
            'address_2'  => $customer->get_shipping_address_2(),
            'city'       => $customer->get_shipping_city(),
            'state'      => $customer->get_shipping_state(),
            'postcode'   => $customer->get_shipping_postcode(),
            'country'    => $customer->get_shipping_country(),
            'email'      => '',
            'phone'      => '',
        ];
    }
}
