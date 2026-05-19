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
            'permission_callback' => '__return_true',
            'args'                => [
                'billing'                   => [ 'required' => true,  'type' => 'object' ],
                'shipping'                  => [ 'required' => false, 'type' => 'object',  'default' => [] ],
                'ship_to_different_address' => [ 'required' => false, 'type' => 'boolean', 'default' => false ],
                'payment_method'            => [ 'required' => true,  'type' => 'string',  'sanitize_callback' => 'sanitize_text_field' ],
                'shipping_method'           => [ 'required' => false, 'type' => 'array',   'default' => [] ],
                'order_notes'               => [ 'required' => false, 'type' => 'string',  'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ],
            ],
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
        $this->ensure_cart();
        $checkout = WC()->checkout();

        $data = apply_filters( 'storefuse_bridge_checkout_config', [
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
        $this->ensure_cart();

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
        $this->ensure_cart();

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
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

        if ( WC()->cart->is_empty() ) {
            return StoreFuse_Bridge_Errors::validation_error( 'Your cart is empty.' );
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
            return StoreFuse_Bridge_Errors::checkout_failed( $e->getMessage() );
        }

        if ( is_wp_error( $order_id ) ) {
            return StoreFuse_Bridge_Errors::checkout_failed( $order_id->get_error_message() );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return StoreFuse_Bridge_Errors::checkout_failed( 'Order could not be retrieved after creation.' );
        }

        do_action( 'woocommerce_checkout_order_created', $order );

        // Process payment
        try {
            $payment_result = $gateway->process_payment( $order_id );
        } catch ( Exception $e ) {
            $order->update_status( 'failed' );
            return StoreFuse_Bridge_Errors::checkout_failed( $e->getMessage() );
        }

        if ( ! is_array( $payment_result ) || ( $payment_result['result'] ?? '' ) !== 'success' ) {
            $notices = wc_get_notices( 'error' );
            $message = ! empty( $notices )
                ? wp_strip_all_tags( $notices[0]['notice'] )
                : 'Payment could not be processed.';
            wc_clear_notices();
            $order->update_status( 'failed' );
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

        return StoreFuse_Bridge_Response::with_no_store( $this->success( $data, 'storefuse.checkout.v1' ) );
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

    /**
     * Validate the X-WC-Nonce header on checkout write operations.
     * Returns null on success, error response on failure.
     */
    private function check_cart_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WC-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    /**
     * Ensure WooCommerce cart and session are initialised before access.
     */
    private function ensure_cart(): void {
        if ( ! WC()->cart ) {
            wc_load_cart();
        }
    }

    /**
     * Validate required billing fields.
     */
    private function validate_billing( array $billing ): ?WP_REST_Response {
        $required = [ 'first_name', 'last_name', 'email', 'address_1', 'city', 'country' ];

        foreach ( $required as $field ) {
            if ( empty( $billing[ $field ] ) ) {
                return StoreFuse_Bridge_Errors::validation_error(
                    sprintf( 'Billing %s is required.', str_replace( '_', ' ', $field ) )
                );
            }
        }

        if ( ! is_email( $billing['email'] ) ) {
            return StoreFuse_Bridge_Errors::invalid_email();
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
    // format_order and format_order_address are now in StoreFuse_Bridge_Format (shared with Orders module).

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
     * @deprecated Delegated to StoreFuse_Bridge_Format::order(). Kept for safety; remove in next cleanup.
     */
    private function format_order( WC_Abstract_Order $order ): array {
        $line_items = [];

        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product_id   = $item->get_product_id();
            $product      = $item->get_product();
            $product_slug = get_post_field( 'post_name', $product_id );
            $thumbnail    = ( $product instanceof WC_Product )
                ? StoreFuse_Bridge_Format::image( (int) $product->get_image_id() )
                : null;

            $line_items[] = [
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => StoreFuse_Bridge_Format::price( (float) $item->get_subtotal() ),
                'total'    => StoreFuse_Bridge_Format::price( (float) $item->get_total() ),
                'product'  => [
                    'id'        => $product_id,
                    'slug'      => $product_slug,
                    'href'      => $product_slug ? '/product/' . $product_slug : '',
                    'thumbnail' => $thumbnail,
                ],
            ];
        }

        return apply_filters( 'storefuse_bridge_order_data', [
            'id'                   => $order->get_id(),
            'number'               => $order->get_order_number(),
            'key'                  => $order->get_order_key(),
            'status'               => $order->get_status(),
            'date_created'         => StoreFuse_Bridge_Format::date(
                $order->get_date_created()
                    ? $order->get_date_created()->date( 'Y-m-d H:i:s' )
                    : ''
            ),
            'currency'             => $order->get_currency(),
            'billing'              => $this->format_order_address( $order, 'billing' ),
            'shipping'             => $this->format_order_address( $order, 'shipping' ),
            'items'                => $line_items,
            'totals'               => [
                'subtotal' => StoreFuse_Bridge_Format::price( (float) $order->get_subtotal() ),
                'discount' => StoreFuse_Bridge_Format::price( (float) $order->get_discount_total() ),
                'shipping' => StoreFuse_Bridge_Format::price( (float) $order->get_shipping_total() ),
                'tax'      => StoreFuse_Bridge_Format::price( (float) $order->get_total_tax() ),
                'total'    => StoreFuse_Bridge_Format::price( (float) $order->get_total( 'edit' ) ),
            ],
            'payment_method'       => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'customer_note'        => $order->get_customer_note(),
            'is_paid'              => $order->is_paid(),
        ], $order );
    }

    /**
     * Normalise billing or shipping address from a WC order.
     */
    private function format_order_address( WC_Abstract_Order $order, string $type ): array {
        if ( $type === 'billing' ) {
            return [
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'company'    => $order->get_billing_company(),
                'address_1'  => $order->get_billing_address_1(),
                'address_2'  => $order->get_billing_address_2(),
                'city'       => $order->get_billing_city(),
                'state'      => $order->get_billing_state(),
                'postcode'   => $order->get_billing_postcode(),
                'country'    => $order->get_billing_country(),
                'email'      => $order->get_billing_email(),
                'phone'      => $order->get_billing_phone(),
            ];
        }

        return [
            'first_name' => $order->get_shipping_first_name(),
            'last_name'  => $order->get_shipping_last_name(),
            'company'    => $order->get_shipping_company(),
            'address_1'  => $order->get_shipping_address_1(),
            'address_2'  => $order->get_shipping_address_2(),
            'city'       => $order->get_shipping_city(),
            'state'      => $order->get_shipping_state(),
            'postcode'   => $order->get_shipping_postcode(),
            'country'    => $order->get_shipping_country(),
            'email'      => '',
            'phone'      => '',
        ];
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
