<?php
defined( 'ABSPATH' ) || exit;

/**
 * Addresses Module
 *
 * Routes (all require login):
 *   GET /storefuse/v1/addresses            - get billing + shipping address
 *   PUT /storefuse/v1/addresses/billing    - update billing address (X-WP-Nonce)
 *   PUT /storefuse/v1/addresses/shipping   - update shipping address (X-WP-Nonce)
 */
class StoreFuse_Bridge_Module_Addresses extends StoreFuse_Bridge_Module {

    protected string $id = 'addresses';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/addresses', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_addresses' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );

        register_rest_route( $this->namespace, '/addresses/billing', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update_billing' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
            'args'                => $this->address_args( true ),
        ] );

        register_rest_route( $this->namespace, '/addresses/shipping', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update_shipping' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
            'args'                => $this->address_args( false ),
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_addresses( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $customer = new WC_Customer( get_current_user_id() );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [
                    'billing'  => $this->format_billing( $customer ),
                    'shipping' => $this->format_shipping( $customer ),
                ],
                'storefuse.addresses.v1'
            )
        );
    }

    public function update_billing( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $customer = new WC_Customer( get_current_user_id() );

        foreach ( $this->billing_fields() as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                $method = 'set_billing_' . $field;
                if ( is_callable( [ $customer, $method ] ) ) {
                    $customer->$method( wc_clean( $value ) );
                }
            }
        }

        $customer->save();

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [ 'billing' => $this->format_billing( $customer ) ],
                'storefuse.addresses.v1'
            )
        );
    }

    public function update_shipping( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $customer = new WC_Customer( get_current_user_id() );

        foreach ( $this->shipping_fields() as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                $method = 'set_shipping_' . $field;
                if ( is_callable( [ $customer, $method ] ) ) {
                    $customer->$method( wc_clean( $value ) );
                }
            }
        }

        $customer->save();

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [ 'shipping' => $this->format_shipping( $customer ) ],
                'storefuse.addresses.v1'
            )
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

    private function billing_fields(): array {
        return [ 'first_name', 'last_name', 'company', 'address_1', 'address_2',
                 'city', 'state', 'postcode', 'country', 'email', 'phone' ];
    }

    private function shipping_fields(): array {
        return [ 'first_name', 'last_name', 'company', 'address_1', 'address_2',
                 'city', 'state', 'postcode', 'country' ];
    }

    private function address_args( bool $include_contact ): array {
        $fields = $include_contact ? $this->billing_fields() : $this->shipping_fields();
        $args   = [];
        foreach ( $fields as $field ) {
            $args[ $field ] = [
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ];
        }
        return $args;
    }

    private function format_billing( WC_Customer $customer ): array {
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

    private function format_shipping( WC_Customer $customer ): array {
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
        ];
    }
}
