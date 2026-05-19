<?php
defined( 'ABSPATH' ) || exit;

/**
 * Orders Module
 *
 * Routes (all require login, checked in handlers):
 *   GET  /storefuse/v1/orders                        - paginated order history
 *   GET  /storefuse/v1/orders/{id}                   - full order by numeric ID
 *   POST /storefuse/v1/orders/{id}/cancel            - cancel cancellable order
 *   POST /storefuse/v1/orders/{id}/reorder           - add order items back to cart (X-WC-Nonce)
 *   POST /storefuse/v1/orders/{id}/return-request    - log a return request as order note
 *   GET  /storefuse/v1/orders/{id}/tracking          - shipping tracking data
 *
 * Note: the public order-confirmation endpoint (GET /orders/{wc_order_key}) lives
 * in the Checkout module so it can be accessed without login.
 */
class StoreFuse_Bridge_Module_Orders extends StoreFuse_Bridge_Module {

    protected string $id = 'orders';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/orders', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_orders' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
            'args'                => [
                'page'     => [ 'required' => false, 'type' => 'integer', 'default' => 1,   'minimum' => 1 ],
                'per_page' => [ 'required' => false, 'type' => 'integer', 'default' => 10,  'minimum' => 1, 'maximum' => 50 ],
                'status'   => [ 'required' => false, 'type' => 'string',  'default' => 'any', 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        // Numeric ID only - order key route is in Checkout module.
        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_order' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );

        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)/cancel', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'cancel_order' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
        ] );

        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)/reorder', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'reorder' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );

        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)/return-request', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'return_request' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login_and_nonce' ],
            'args'                => [
                'reason' => [ 'required' => false, 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)/tracking', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_tracking' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );

        register_rest_route( $this->namespace, '/orders/(?P<id>\d+)/invoice', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_invoice' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Permissions', 'require_login' ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    public function get_orders( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $user_id  = get_current_user_id();
        $page     = (int) $request->get_param( 'page' );
        $per_page = (int) $request->get_param( 'per_page' );
        $status   = $request->get_param( 'status' );

        $args = [
            'customer' => $user_id,
            'limit'    => $per_page,
            'page'     => $page,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'return'   => 'objects',
        ];

        if ( $status !== 'any' ) {
            // Accept with or without 'wc-' prefix
            $args['status'] = 'wc-' . ltrim( $status, 'wc-' );
        }

        $orders = wc_get_orders( $args );

        // Count without pagination for meta
        $count_args           = $args;
        $count_args['limit']  = -1;
        $count_args['return'] = 'ids';
        unset( $count_args['page'] );
        $total = count( wc_get_orders( $count_args ) );

        $response = $this->success(
            [
                'orders' => array_map(
                    [ 'StoreFuse_Bridge_Format', 'order_summary' ],
                    $orders
                ),
                'meta'   => [
                    'total'       => $total,
                    'page'        => $page,
                    'per_page'    => $per_page,
                    'total_pages' => (int) ceil( $total / $per_page ),
                ],
            ],
            'storefuse.orders.v1'
        );

        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    public function get_order( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( StoreFuse_Bridge_Format::order( $order ), 'storefuse.order.v1' )
        );
    }

    public function cancel_order( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        $cancellable = [ 'pending', 'processing', 'on-hold' ];
        if ( ! in_array( $order->get_status(), $cancellable, true ) ) {
            return StoreFuse_Bridge_Errors::validation_error(
                sprintf( 'Orders with status "%s" cannot be cancelled.', $order->get_status() )
            );
        }

        $order->update_status(
            'cancelled',
            __( 'Order cancelled by customer via StoreFuse.', 'storefuse-bridge' )
        );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( StoreFuse_Bridge_Format::order( $order ), 'storefuse.order.v1' )
        );
    }

    public function reorder( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        // Reorder writes to the cart, so requires the WC cart nonce.
        $nonce = $request->get_header( 'X-WC-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        if ( ! WC()->cart ) {
            wc_load_cart();
        }

        $skipped = [];

        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product = $item->get_product();

            if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
                $skipped[] = $item->get_name();
                continue;
            }

            WC()->cart->add_to_cart(
                $item->get_product_id(),
                $item->get_quantity(),
                $item->get_variation_id(),
                $item->get_variation()
            );
        }

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success(
                [
                    'added'         => true,
                    'skipped_items' => $skipped,
                    'cart_url'      => wc_get_cart_url(),
                ],
                'storefuse.orders.v1'
            )
        );
    }

    public function return_request( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $nonce_error = $this->check_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        if ( $order->get_status() !== 'completed' ) {
            return StoreFuse_Bridge_Errors::validation_error(
                'Return requests can only be submitted for completed orders.'
            );
        }

        $reason = $request->get_param( 'reason' ) ?? '';
        $note   = __( 'Return requested by customer via StoreFuse.', 'storefuse-bridge' );
        if ( $reason ) {
            $note .= ' ' . __( 'Reason:', 'storefuse-bridge' ) . ' ' . $reason;
        }

        $order->add_order_note( $note );
        do_action( 'storefuse_bridge_order_return_requested', $order, $reason );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( [ 'success' => true ], 'storefuse.orders.v1' )
        );
    }

    public function get_tracking( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        // Tracking data is stored in order meta by shipping plugins (Shipment Tracking, etc.).
        // Expose via a filter so any tracking plugin can hook in and return structured data.
        $tracking = apply_filters( 'storefuse_bridge_order_tracking', [
            'tracking_number' => $order->get_meta( '_tracking_number', true ) ?: $order->get_meta( 'tracking_number', true ),
            'tracking_url'    => $order->get_meta( '_tracking_url', true )    ?: $order->get_meta( 'tracking_url', true ),
            'carrier'         => $order->get_meta( '_tracking_provider', true ) ?: $order->get_meta( 'tracking_provider', true ),
            'shipped_date'    => StoreFuse_Bridge_Format::date(
                (string) ( $order->get_meta( '_tracking_shipped_date', true ) ?: '' )
            ),
        ], $order );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( $tracking, 'storefuse.orders.v1' )
        );
    }

    /**
     * GET /orders/{id}/invoice
     *
     * Returns a structured invoice for the order: items, totals, billing/shipping address.
     * Includes a print_url pointing to the native WooCommerce view-order page as fallback.
     * PDF invoice plugins can override print_url via the storefuse_bridge_invoice_print_url filter.
     */
    public function get_invoice( WP_REST_Request $request ): WP_REST_Response {
        if ( ! is_user_logged_in() ) {
            return StoreFuse_Bridge_Errors::not_authenticated();
        }

        $order = $this->resolve_order( (int) $request->get_param( 'id' ) );
        if ( $order instanceof WP_REST_Response ) {
            return $order;
        }

        $items = [];
        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product = $item->get_product();
            $items[] = [
                'name'     => $item->get_name(),
                'sku'      => $product ? $product->get_sku() : '',
                'quantity' => $item->get_quantity(),
                'subtotal' => StoreFuse_Bridge_Format::price( (float) $item->get_subtotal() ),
                'total'    => StoreFuse_Bridge_Format::price( (float) $item->get_total() ),
            ];
        }

        $date_created = $order->get_date_created();

        $invoice = apply_filters( 'storefuse_bridge_order_invoice', [
            'invoice_number' => 'INV-' . $order->get_order_number(),
            'order_number'   => $order->get_order_number(),
            'order_status'   => $order->get_status(),
            'date_issued'    => $date_created ? StoreFuse_Bridge_Format::date( $date_created->format( 'Y-m-d H:i:s' ) ) : null,
            'billing_to'     => StoreFuse_Bridge_Format::order_address( $order, 'billing' ),
            'shipping_to'    => StoreFuse_Bridge_Format::order_address( $order, 'shipping' ),
            'items'          => $items,
            'totals'         => [
                'subtotal' => StoreFuse_Bridge_Format::price( (float) $order->get_subtotal() ),
                'shipping' => StoreFuse_Bridge_Format::price( (float) $order->get_shipping_total() ),
                'tax'      => StoreFuse_Bridge_Format::price( (float) $order->get_total_tax() ),
                'discount' => StoreFuse_Bridge_Format::price( (float) $order->get_total_discount() ),
                'total'    => StoreFuse_Bridge_Format::price( (float) $order->get_total() ),
            ],
            'payment_method' => $order->get_payment_method_title(),
            'customer_note'  => $order->get_customer_note(),
            'print_url'      => apply_filters(
                'storefuse_bridge_invoice_print_url',
                wc_get_endpoint_url( 'view-order', (string) $order->get_id(), wc_get_account_endpoint_url( 'orders' ) ),
                $order
            ),
        ], $order );

        return StoreFuse_Bridge_Response::with_no_store(
            $this->success( $invoice, 'storefuse.order.v1' )
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

    /**
     * Load and ownership-verify a WC order.
     * Returns the order object or an error WP_REST_Response.
     *
     * @return WC_Order|WP_REST_Response
     */
    private function resolve_order( int $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return StoreFuse_Bridge_Errors::order_not_found();
        }

        $current_user_id = get_current_user_id();
        if (
            (int) $order->get_customer_id() !== $current_user_id
            && ! current_user_can( 'manage_woocommerce' )
        ) {
            return StoreFuse_Bridge_Errors::forbidden();
        }

        return $order;
    }
}
