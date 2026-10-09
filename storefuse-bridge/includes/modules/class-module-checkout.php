<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout Module
 *
 * Routes:
 *   GET  /storefuse/v1/checkout/config
 *   GET  /storefuse/v1/checkout/payment-methods
 *   GET  /storefuse/v1/checkout/shipping-methods
 *   POST /storefuse/v1/checkout
 *   GET  /storefuse/v1/orders/{key}     (order confirmation by order key - public)
 *
 * All responses carry Cache-Control: no-store.
 * POST /checkout requires a valid X-WC-Nonce header.
 */
class StoreFuse_Bridge_Module_Checkout extends StoreFuse_Bridge_Module {

    protected string $id = 'checkout';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/checkout/config', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_config' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/checkout/payment-methods', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_payment_methods' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/checkout/shipping-methods', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_shipping_methods' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'country'  => [ 'required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'state'    => [ 'required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'postcode' => [ 'required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'city'     => [ 'required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/checkout', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'process_checkout' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'checkout_permission' ],
            'args'                => [
                'billing'                   => [ 'required' => true,  'type' => 'object' ],
                'shipping'                  => [ 'required' => false, 'type' => 'object',  'default' => [] ],
                'ship_to_different_address' => [ 'required' => false, 'type' => 'boolean', 'default' => false ],
                'payment_method'            => [ 'required' => true,  'type' => 'string',  'sanitize_callback' => 'sanitize_text_field' ],
                'shipping_method'           => [ 'required' => false, 'type' => 'array',   'default' => [] ],
                'order_notes'               => [ 'required' => false, 'type' => 'string',  'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ],
            ],
        ] );

        // Redirect mode: return the native WooCommerce checkout URL for the current cart.
        // No nonce required - no state is changed, just a URL is returned.
        register_rest_route( $this->namespace, '/checkout/redirect-url', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'get_redirect_url' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
        ] );

        // Order confirmation - order key acts as a public access token.
        // Pattern requires a non-numeric prefix so pure numeric IDs route to the Orders module instead.
        register_rest_route( $this->namespace, '/orders/(?P<key>wc_order_[a-zA-Z0-9]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_order_by_key' ],
            'permission_callback' => '__return_true',
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    /**
     * GET /checkout/config
     *
     * Returns checkout field definitions, cart shipping status, and country/state
     * lists for building the checkout form dynamically on the storefront.
     */
    public function get_config( WP_REST_Request $request ): WP_REST_Response {
        StoreFuse_Bridge_Auth::ensure_cart();
        $checkout = WC()->checkout();

        $data = apply_filters( 'storefuse_bridge_checkout_config', [
            'checkout_mode'                => StoreFuse_Bridge_Settings::get( 'checkout_mode', 'redirect' ),
            'checkout_redirect_label'      => StoreFuse_Bridge_Settings::get( 'checkout_redirect_label', 'Proceed to Checkout' ),
            'checkout_page_url'            => StoreFuse_Bridge_Settings::get( 'checkout_page_url', '' ) ?: wc_get_checkout_url(),
            'billing_fields'               => $this->format_fields( $checkout->get_checkout_fields( 'billing' ) ),
            'shipping_fields'              => $this->format_fields( $checkout->get_checkout_fields( 'shipping' ) ),
            'has_shipping'                 => WC()->cart->needs_shipping(),
            'ship_to_different_address'    => (bool) WC()->session->get( 'ship_to_different_address' ),
            'order_notes_enabled'          => apply_filters(
                'woocommerce_enable_order_notes_field',
                get_option( 'woocommerce_enable_order_comments', 'yes' ) === 'yes'
            ),
            'terms_and_conditions_page_id' => (int) get_option( 'woocommerce_terms_page_id', 0 ),
            'allowed_countries'            => WC()->countries->get_allowed_countries(),
            'states'                       => WC()->countries->get_allowed_country_states(),
        ] );

        return StoreFuse_Bridge_Response::with_no_store( $this->success( $data, 'storefuse.checkout.v1' ) );
    }

    /**
     * GET /checkout/payment-methods
     *
     * Returns all active and available payment gateways for the current cart.
     */
    public function get_payment_methods( WP_REST_Request $request ): WP_REST_Response {
        StoreFuse_Bridge_Auth::ensure_cart();

        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $methods  = [];

        foreach ( $gateways as $gateway ) {
            $methods[] = [
                'id'                => $gateway->id,
                'title'             => $gateway->get_title(),
                'description'       => $gateway->get_description(),
                'icon_url'          => $gateway->get_icon() ? $this->extract_icon_url( $gateway->get_icon() ) : '',
                'order_button_text' => $gateway->order_button_text ?? '',
                'supports'          => $gateway->supports ?? [],
            ];
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                apply_filters( 'storefuse_bridge_payment_methods', $methods ),
                'storefuse.checkout.v1'
            )
        );
    }

    /**
     * GET /checkout/shipping-methods
     *
     * Returns available shipping methods for the current cart.
     * Pass country, state, postcode, city to calculate for a specific destination.
     * Passing an address updates the customer's shipping address in the session.
     */
    public function get_shipping_methods( WP_REST_Request $request ): WP_REST_Response {
        StoreFuse_Bridge_Auth::ensure_cart();

        if ( WC()->cart->is_empty() ) {
            return StoreFuse_Bridge_Response::with_no_store(
                $this->success( [ 'packages' => [] ], 'storefuse.checkout.v1' )
            );
        }

        $country  = $request->get_param( 'country' );
        $state    = $request->get_param( 'state' );
        $postcode = $request->get_param( 'postcode' );
        $city     = $request->get_param( 'city' );

        if ( $country ) {
            WC()->customer->set_shipping_country( $country );
            WC()->customer->set_shipping_state( $state );
            WC()->customer->set_shipping_postcode( $postcode );
            WC()->customer->set_shipping_city( $city );
            WC()->customer->save();
        }

        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        $packages       = WC()->shipping()->get_packages();
        $chosen_methods = (array) WC()->session->get( 'chosen_shipping_methods', [] );
        $result         = [];

        foreach ( $packages as $package_index => $package ) {
            $methods   = [];
            $chosen_id = $chosen_methods[ $package_index ] ?? null;

            foreach ( $package['rates'] as $rate_id => $rate ) {
                $methods[] = [
                    'id'          => $rate->get_id(),
                    'instance_id' => $rate->get_instance_id(),
                    'label'       => $rate->get_label(),
                    'cost'        => StoreFuse_Bridge_Format::price( (float) $rate->get_cost() ),
                    'taxes'       => StoreFuse_Bridge_Format::price( (float) array_sum( $rate->get_taxes() ) ),
                    'is_selected' => $chosen_id !== null
                        ? ( $chosen_id === $rate_id )
                        : ( $rate_id === array_key_first( $package['rates'] ) ),
                ];
            }

            $result[] = [
                'index'       => $package_index,
                'destination' => [
                    'country'  => $package['destination']['country']  ?? '',
                    'state'    => $package['destination']['state']    ?? '',
                    'postcode' => $package['destination']['postcode'] ?? '',
                    'city'     => $package['destination']['city']     ?? '',
                ],
                'methods' => $methods,
            ];
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                apply_filters( 'storefuse_bridge_shipping_packages', [ 'packages' => $result ] ),
                'storefuse.checkout.v1'
            )
        );
    }

    /**
     * POST /checkout
     *
     * Places an order from the current WooCommerce cart session.
     *
     * Handles both inline gateways (COD, BACS - order immediately processing/on-hold)
     * and redirect gateways (PayPal, Razorpay - order stays pending, client redirects).
     *
     * Payment result type:
     *   'success'  - redirect_url points to our own site (thank-you page)
     *   'redirect' - redirect_url points to an external payment gateway
     *
     * Requires X-WC-Nonce header.
     */
    public function process_checkout( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $idempotency_key = sanitize_text_field( (string) ( $request->get_header( 'Idempotency-Key' ) ?? '' ) );
        if ( $idempotency_key !== '' ) {
            $cached = get_transient( 'sfb_idem_' . md5( $idempotency_key ) );
            if ( is_array( $cached ) && isset( $cached['body'], $cached['status'] ) ) {
                return StoreFuse_Bridge_Response::with_no_store(
                    new WP_REST_Response( $cached['body'], (int) $cached['status'] )
                );
            }
        }

        if ( WC()->cart->is_empty() ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Your cart is empty.' );
        }

        $stock_error = $this->validate_cart_stock();
        if ( $stock_error ) {
            return $stock_error;
        }

        $billing           = (array) $request->get_param( 'billing' );
        $shipping          = (array) $request->get_param( 'shipping' );
        $ship_to_different = (bool) $request->get_param( 'ship_to_different_address' );
        $payment_method_id = sanitize_text_field( $request->get_param( 'payment_method' ) );
        $shipping_methods  = (array) $request->get_param( 'shipping_method' );
        $order_notes       = $request->get_param( 'order_notes' ) ?? '';

        // Validate gateway
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        if ( ! isset( $gateways[ $payment_method_id ] ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Invalid payment method.' );
        }
        $gateway = $gateways[ $payment_method_id ];

        // Validate required billing fields
        $billing_error = $this->validate_billing( $billing );
        if ( $billing_error ) {
            return $billing_error;
        }

        // Set chosen shipping methods before calculating totals
        if ( ! empty( $shipping_methods ) ) {
            WC()->session->set( 'chosen_shipping_methods', array_values( $shipping_methods ) );
        }

        // Push billing/shipping into WC customer (needed for shipping calc and order creation)
        $this->apply_customer_address( $billing, $shipping, $ship_to_different );
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        $shipping_error = $this->validate_shipping_methods( $shipping_methods );
        if ( $shipping_error ) {
            return $shipping_error;
        }

        // Build data array for WC_Checkout::create_order()
        $checkout_data = $this->build_checkout_data(
            $billing,
            $shipping,
            $ship_to_different,
            $payment_method_id,
            $gateway->get_title(),
            $order_notes
        );

        // Create the order
        try {
            $order_id = WC()->checkout()->create_order( $checkout_data );
        } catch ( Exception $e ) {
            StoreFuse_Bridge_Logger::error( 'Checkout create_order exception', [ 'message' => $e->getMessage() ] );
            return StoreFuse_Bridge_Errors::checkout_failed( $e->getMessage() );
        }

        if ( is_wp_error( $order_id ) ) {
            StoreFuse_Bridge_Logger::error( 'Checkout create_order failed', [ 'message' => $order_id->get_error_message() ] );
            return StoreFuse_Bridge_Errors::checkout_failed( $order_id->get_error_message() );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            StoreFuse_Bridge_Logger::error( 'Checkout order missing after create', [ 'order_id' => (int) $order_id ] );
            return StoreFuse_Bridge_Errors::checkout_failed( 'Order could not be retrieved after creation.' );
        }

        do_action( 'woocommerce_checkout_order_created', $order );
        do_action( 'woocommerce_checkout_order_processed', $order_id, $checkout_data, $order );

        // Process payment
        try {
            $payment_result = $gateway->process_payment( $order_id );
        } catch ( Exception $e ) {
            $order->update_status( 'failed' );
            StoreFuse_Bridge_Logger::error( 'Checkout payment exception', [ 'order_id' => $order_id, 'message' => $e->getMessage() ] );
            return StoreFuse_Bridge_Errors::checkout_failed( $e->getMessage() );
        }

        if ( ! is_array( $payment_result ) || ( $payment_result['result'] ?? '' ) !== 'success' ) {
            $notices = wc_get_notices( 'error' );
            $message = ! empty( $notices )
                ? wp_strip_all_tags( $notices[0]['notice'] )
                : 'Payment could not be processed.';
            wc_clear_notices();
            $order->update_status( 'failed' );
            StoreFuse_Bridge_Logger::warning( 'Checkout payment declined', [ 'order_id' => $order_id ] );
            return StoreFuse_Bridge_Errors::checkout_failed( $message );
        }

        WC()->cart->empty_cart();
        wc_clear_notices();

        $redirect_url = $payment_result['redirect'] ?? wc_get_endpoint_url(
            'order-received',
            $order_id,
            wc_get_checkout_url()
        );

        // 'success' = redirect points to our own site (thank-you page)
        // 'redirect' = redirect points to an external payment gateway
        $type = str_starts_with( $redirect_url, trailingslashit( get_site_url() ) )
            ? 'success'
            : 'redirect';

        $data = apply_filters( 'storefuse_bridge_checkout_result', [
            'order_id'       => $order_id,
            'order_key'      => $order->get_order_key(),
            'order_number'   => $order->get_order_number(),
            'order_status'   => $order->get_status(),
            'payment_result' => [
                'type'         => $type,
                'redirect_url' => $redirect_url,
            ],
        ], $order );

        $response = StoreFuse_Bridge_Response::with_no_store( $this->success( $data, 'storefuse.checkout.v1' ) );

        if ( $idempotency_key !== '' ) {
            set_transient(
                'sfb_idem_' . md5( $idempotency_key ),
                [
                    'status' => $response->get_status(),
                    'body'   => $response->get_data(),
                ],
                DAY_IN_SECONDS
            );
        }

        return $response;
    }

    /**
     * POST /checkout/redirect-url
     *
     * Redirect checkout mode: returns the WooCommerce checkout URL for the current cart.
     * The storefront redirects the user to this URL; WooCommerce handles everything from here.
     * No nonce required - this is a read-only URL lookup, no state is changed.
     */
    public function get_redirect_url( WP_REST_Request $request ): WP_REST_Response {
        StoreFuse_Bridge_Auth::ensure_cart();

        if ( WC()->cart->is_empty() ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Your cart is empty.' );
        }

        $checkout_url = apply_filters( 'storefuse_bridge_checkout_redirect_url', wc_get_checkout_url() );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( [ 'redirect_url' => esc_url_raw( $checkout_url ) ], 'storefuse.checkout.v1' )
        );
    }

    /**
     * GET /orders/{key}
     *
     * Returns full order details by order key.
     * Intended for the thank-you / order confirmation page.
     *
     * The order key acts as a short-lived public token. If a logged-in customer
     * requests an order that belongs to a different customer, a 403 is returned.
     * Guests can always access an order they hold the key for.
     */
    public function get_order_by_key( WP_REST_Request $request ): WP_REST_Response {
        $order_key = sanitize_text_field( $request->get_param( 'key' ) );
        $order_id  = wc_get_order_id_by_order_key( $order_key );

        if ( ! $order_id ) {
            return StoreFuse_Bridge_Errors::order_not_found();
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return StoreFuse_Bridge_Errors::order_not_found();
        }

        // If the request comes from a logged-in user, they may only view their own orders.
        if ( is_user_logged_in() ) {
            $current_user_id = get_current_user_id();
            if (
                (int) $order->get_customer_id() !== $current_user_id
                && ! current_user_can( 'manage_woocommerce' )
            ) {
                return StoreFuse_Bridge_Errors::forbidden();
            }
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( StoreFuse_Bridge_Format::order( $order ), 'storefuse.order.v1' )
        );
    }

    // ── Helpers 

    private function validate_billing( array $billing ): ?WP_REST_Response {
        $fields = WC()->checkout()->get_checkout_fields( 'billing' );

        foreach ( $fields as $key => $field ) {
            if ( empty( $field['required'] ) ) {
                continue;
            }
            $name = (string) preg_replace( '/^billing_/', '', $key );
            if ( empty( $billing[ $name ] ) ) {
                return StoreFuse_Bridge_Errors::validation_error(
                    sprintf( 'Billing %s is required.', str_replace( '_', ' ', $name ) )
                );
            }
        }

        if ( ! empty( $billing['email'] ) && ! is_email( $billing['email'] ) ) {
            return StoreFuse_Bridge_Errors::invalid_email();
        }

        return null;
    }

    private function validate_cart_stock(): ?WP_REST_Response {
        foreach ( WC()->cart->get_cart() as $item ) {
            $product = $item['data'] ?? null;
            if ( ! ( $product instanceof WC_Product ) ) {
                continue;
            }
            if ( ! $product->is_purchasable() ) {
                return StoreFuse_Bridge_Errors::validation_error(
                    sprintf( '%s is not available for purchase.', $product->get_name() )
                );
            }
            if ( ! $product->has_enough_stock( $item['quantity'] ) ) {
                return StoreFuse_Bridge_Errors::out_of_stock();
            }
        }
        return null;
    }

    /**
     * @param list<string> $shipping_methods
     */
    private function validate_shipping_methods( array $shipping_methods ): ?WP_REST_Response {
        if ( ! WC()->cart->needs_shipping() || empty( $shipping_methods ) ) {
            return null;
        }

        $packages = WC()->shipping()->get_packages();
        foreach ( $packages as $index => $package ) {
            $chosen = $shipping_methods[ $index ] ?? $shipping_methods[0] ?? '';
            if ( $chosen === '' ) {
                continue;
            }
            if ( ! isset( $package['rates'][ $chosen ] ) ) {
                return StoreFuse_Bridge_Errors::validation_error( 'Selected shipping method is not available.' );
            }
        }

        return null;
    }

    /**
     * Push billing and shipping addresses into the WC customer object.
     * WooCommerce reads these when creating the order and calculating shipping.
     */
    private function apply_customer_address( array $billing, array $shipping, bool $ship_to_different ): void {
        $billing_map = [
            'first_name' => 'set_billing_first_name',
            'last_name'  => 'set_billing_last_name',
            'email'      => 'set_billing_email',
            'phone'      => 'set_billing_phone',
            'address_1'  => 'set_billing_address_1',
            'address_2'  => 'set_billing_address_2',
            'city'       => 'set_billing_city',
            'state'      => 'set_billing_state',
            'postcode'   => 'set_billing_postcode',
            'country'    => 'set_billing_country',
            'company'    => 'set_billing_company',
        ];

        foreach ( $billing_map as $field => $method ) {
            if ( isset( $billing[ $field ] ) ) {
                WC()->customer->$method( wc_clean( (string) $billing[ $field ] ) );
            }
        }

        $shipping_source = $ship_to_different ? $shipping : $billing;
        $shipping_map    = [
            'first_name' => 'set_shipping_first_name',
            'last_name'  => 'set_shipping_last_name',
            'address_1'  => 'set_shipping_address_1',
            'address_2'  => 'set_shipping_address_2',
            'city'       => 'set_shipping_city',
            'state'      => 'set_shipping_state',
            'postcode'   => 'set_shipping_postcode',
            'country'    => 'set_shipping_country',
            'company'    => 'set_shipping_company',
        ];

        foreach ( $shipping_map as $field => $method ) {
            if ( isset( $shipping_source[ $field ] ) ) {
                WC()->customer->$method( wc_clean( (string) $shipping_source[ $field ] ) );
            }
        }

        WC()->customer->save();
    }

    /**
     * Build the data array expected by WC_Checkout::create_order().
     * Keys must be prefixed with billing_ or shipping_ to match WC checkout field names.
     */
    private function build_checkout_data(
        array  $billing,
        array  $shipping,
        bool   $ship_to_different,
        string $payment_method_id,
        string $payment_method_title,
        string $order_notes
    ): array {
        $data = [
            'payment_method'            => $payment_method_id,
            'payment_method_title'      => $payment_method_title,
            'ship_to_different_address' => $ship_to_different ? 1 : 0,
            'order_comments'            => $order_notes,
        ];

        foreach ( $billing as $field => $value ) {
            $data[ 'billing_' . sanitize_key( $field ) ] = wc_clean( (string) $value );
        }

        $shipping_source = $ship_to_different ? $shipping : $billing;
        foreach ( $shipping_source as $field => $value ) {
            // Shipping address has no email or phone fields in WC
            if ( in_array( $field, [ 'email', 'phone' ], true ) ) {
                continue;
            }
            $data[ 'shipping_' . sanitize_key( $field ) ] = wc_clean( (string) $value );
        }

        return $data;
    }

    /**
     * Normalise WC checkout fields array into the StoreFuse field shape.
     */
    private function format_fields( array $wc_fields ): array {
        $result = [];

        foreach ( $wc_fields as $key => $field ) {
            $name     = (string) preg_replace( '/^(billing|shipping)_/', '', $key );
            $result[] = [
                'key'          => $key,
                'name'         => $name,
                'label'        => $field['label']        ?? '',
                'placeholder'  => $field['placeholder']  ?? '',
                'type'         => $field['type']          ?? 'text',
                'required'     => ! empty( $field['required'] ),
                'autocomplete' => $field['autocomplete']  ?? '',
                'options'      => $field['options']       ?? [],
                'priority'     => (int) ( $field['priority'] ?? 10 ),
            ];
        }

        usort( $result, static fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

        return $result;
    }

    /**
     * Extract the src URL from a gateway icon HTML string.
     * WC gateways return icon as an <img> tag, not a raw URL.
     */
    private function extract_icon_url( string $icon_html ): string {
        if ( preg_match( '/src=["\']([^"\']+)["\']/', $icon_html, $matches ) ) {
            return $matches[1];
        }
        return '';
    }
}
