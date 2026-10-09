<?php
defined( 'ABSPATH' ) || exit;

/**
 * Cart Module
 *
 * Routes:
 *   GET    /storefuse/v1/cart
 *   POST   /storefuse/v1/cart/add
 *   PUT    /storefuse/v1/cart/update
 *   DELETE /storefuse/v1/cart/remove
 *   POST   /storefuse/v1/cart/coupon
 *   DELETE /storefuse/v1/cart/coupon
 *
 * All responses carry Cache-Control: no-store and X-StoreFuse-Cart-Token.
 * Write operations (add, update, remove, coupon) require a valid X-WC-Nonce header.
 * GET /cart is public - any session (guest or logged-in) can read its own cart.
 */
class StoreFuse_Bridge_Module_Cart extends StoreFuse_Bridge_Module {

    protected string $id = 'cart';

    public function register_routes(): void {

        register_rest_route( $this->namespace, '/cart', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_cart' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $this->namespace, '/cart/add', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'add_item' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
            'args'                => [
                'product_id'   => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'variation_id' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ],
                'quantity'     => [
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 1,
                    'minimum'  => 1,
                ],
                'variation'    => [
                    'required' => false,
                    'type'     => 'object',
                    'default'  => [],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/cart/update', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update_item' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
            'args'                => [
                'cart_item_key' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'quantity'      => [
                    'required' => true,
                    'type'     => 'integer',
                    'minimum'  => 0,
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/cart/remove', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'remove_item' ],
            'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
            'args'                => [
                'cart_item_key' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // POST and DELETE share the same /cart/coupon path
        register_rest_route( $this->namespace, '/cart/coupon', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'apply_coupon' ],
                'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
                'args'                => [
                    'code' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'remove_coupon' ],
                'permission_callback' => [ 'StoreFuse_Bridge_Auth', 'cart_permission' ],
                'args'                => [
                    'code' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );
    }

    // ── Handlers ─────────────────────────────────────────────────────────────

    /**
     * GET /cart
     *
     * Returns the WooCommerce cart for the current session.
     * Works for both guests and logged-in customers.
     */
    public function get_cart( WP_REST_Request $request ): WP_REST_Response {
        StoreFuse_Bridge_Auth::ensure_cart();

        return $this->cart_response();
    }

    /**
     * POST /cart/add
     *
     * Add a product or product variation to the cart.
     * Pass `variation_id` and a `variation` map for variable products.
     */
    public function add_item( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $product_id   = (int) $request->get_param( 'product_id' );
        $variation_id = (int) $request->get_param( 'variation_id' );
        $quantity     = max( 1, (int) $request->get_param( 'quantity' ) );
        $variation    = (array) $request->get_param( 'variation' );

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->exists() ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        $line_product = $variation_id > 0 ? wc_get_product( $variation_id ) : $product;
        if ( ! $line_product || ! $line_product->exists() ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        if ( ! $line_product->is_in_stock() || ! $line_product->is_purchasable() ) {
            return StoreFuse_Bridge_Errors::out_of_stock();
        }

        $min_qty = max( 1, (int) $line_product->get_min_purchase_quantity() );
        $max_qty = (int) $line_product->get_max_purchase_quantity();
        if ( $quantity < $min_qty ) {
            return StoreFuse_Bridge_Errors::quantity_below_minimum( $min_qty );
        }
        if ( $max_qty > 0 && $quantity > $max_qty ) {
            return StoreFuse_Bridge_Errors::quantity_above_maximum( $max_qty );
        }

        if ( $line_product->is_sold_individually() ) {
            if ( $quantity > 1 ) {
                return StoreFuse_Bridge_Errors::sold_individually();
            }
            foreach ( WC()->cart->get_cart() as $item ) {
                if ( (int) $item['product_id'] === $product_id && (int) $item['variation_id'] === $variation_id ) {
                    return StoreFuse_Bridge_Errors::sold_individually();
                }
            }
        }

        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            $quantity,
            $variation_id,
            $variation
        );

        if ( $cart_item_key === false ) {
            $notices = wc_get_notices( 'error' );
            $message = ! empty( $notices )
                ? wp_strip_all_tags( $notices[0]['notice'] )
                : 'Could not add item to cart.';
            wc_clear_notices();
            return StoreFuse_Bridge_Errors::validation_error( $message );
        }

        WC()->cart->calculate_totals();
        wc_clear_notices();

        return $this->cart_response();
    }

    /**
     * PUT /cart/update
     *
     * Update the quantity of a cart line item.
     * Set quantity to 0 to remove the item.
     */
    public function update_item( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $cart_item_key = $request->get_param( 'cart_item_key' );
        $quantity      = (int) $request->get_param( 'quantity' );

        $cart_line = WC()->cart->get_cart()[ $cart_item_key ] ?? null;
        if ( ! is_array( $cart_line ) ) {
            return StoreFuse_Bridge_Errors::cart_item_not_found();
        }

        if ( $quantity <= 0 ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        } else {
            /** @var WC_Product|null $line_product */
            $line_product = $cart_line['data'] ?? null;
            if ( $line_product instanceof WC_Product ) {
                $min_qty = max( 1, (int) $line_product->get_min_purchase_quantity() );
                $max_qty = (int) $line_product->get_max_purchase_quantity();
                if ( $quantity < $min_qty ) {
                    return StoreFuse_Bridge_Errors::quantity_below_minimum( $min_qty );
                }
                if ( $max_qty > 0 && $quantity > $max_qty ) {
                    return StoreFuse_Bridge_Errors::quantity_above_maximum( $max_qty );
                }
                if ( $line_product->is_sold_individually() && $quantity > 1 ) {
                    return StoreFuse_Bridge_Errors::sold_individually();
                }
            }
            WC()->cart->set_quantity( $cart_item_key, $quantity );
        }

        WC()->cart->calculate_totals();

        return $this->cart_response();
    }

    /**
     * DELETE /cart/remove
     *
     * Remove a specific line item from the cart by its cart_item_key.
     */
    public function remove_item( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $cart_item_key = $request->get_param( 'cart_item_key' );

        if ( ! isset( WC()->cart->get_cart()[ $cart_item_key ] ) ) {
            return StoreFuse_Bridge_Errors::cart_item_not_found();
        }

        WC()->cart->remove_cart_item( $cart_item_key );
        WC()->cart->calculate_totals();

        return $this->cart_response();
    }

    /**
     * POST /cart/coupon
     *
     * Apply a coupon code to the cart.
     */
    public function apply_coupon( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $code = wc_format_coupon_code( $request->get_param( 'code' ) );

        if ( WC()->cart->has_discount( $code ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'This coupon has already been applied to your cart.' );
        }

        $result = WC()->cart->apply_coupon( $code );

        if ( ! $result ) {
            $notices = wc_get_notices( 'error' );
            $message = ! empty( $notices )
                ? wp_strip_all_tags( $notices[0]['notice'] )
                : 'Invalid coupon code.';
            wc_clear_notices();
            return StoreFuse_Bridge_Errors::validation_error( $message );
        }

        wc_clear_notices();
        WC()->cart->calculate_totals();

        return $this->cart_response();
    }

    /**
     * DELETE /cart/coupon
     *
     * Remove an applied coupon from the cart.
     */
    public function remove_coupon( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        StoreFuse_Bridge_Auth::ensure_cart();

        $code = wc_format_coupon_code( $request->get_param( 'code' ) );

        if ( ! WC()->cart->has_discount( $code ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'This coupon is not applied to your cart.' );
        }

        WC()->cart->remove_coupon( $code );
        WC()->cart->calculate_totals();

        return $this->cart_response();
    }

    // ── Helpers 

    /**
     * Build the standard cart response with session headers.
     */
    private function cart_response(): WP_REST_Response {
        $cart_nonce = StoreFuse_Bridge_Auth::generate_storefront_nonce();
        $response   = $this->success( $this->format_cart( $cart_nonce ), 'storefuse.cart.v1' );
        $response->header( 'X-WC-Nonce', $cart_nonce );
        $response   = StoreFuse_Bridge_Session::set_cart_token_header( $response );
        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * Normalise the WooCommerce cart into the StoreFuse cart shape.
     */
    private function format_cart( ?string $cart_nonce = null ): array {
        $cart = WC()->cart;
        if ( $cart_nonce === null ) {
            $cart_nonce = StoreFuse_Bridge_Auth::generate_storefront_nonce();
        }

        return apply_filters( 'storefuse_bridge_cart_data', [
            'items'          => $this->format_cart_items( $cart->get_cart() ),
            'coupons'        => $this->format_coupons( $cart->get_applied_coupons() ),
            'totals'         => $this->format_totals( $cart ),
            'item_count'     => $cart->get_cart_contents_count(),
            'needs_shipping' => $cart->needs_shipping(),
            'is_empty'       => $cart->is_empty(),
            'cart_nonce'     => $cart_nonce,
        ] );
    }

    /**
     * Normalise WC cart items into the StoreFuse cart item shape.
     */
    private function format_cart_items( array $cart_items ): array {
        $items = [];

        foreach ( $cart_items as $cart_item_key => $item ) {
            $formatted = StoreFuse_Bridge_Format::cart_item( $cart_item_key, $item );
            if ( $formatted !== null ) {
                $items[] = $formatted;
            }
        }

        return $items;
    }

    /**
     * Normalise applied coupons with their individual discount amounts.
     */
    private function format_coupons( array $coupon_codes ): array {
        $coupons = [];

        foreach ( $coupon_codes as $code ) {
            $coupons[] = [
                'code'     => $code,
                'discount' => StoreFuse_Bridge_Format::price(
                    (float) WC()->cart->get_coupon_discount_amount( $code )
                ),
            ];
        }

        return $coupons;
    }

    /**
     * Normalise WC cart totals into the StoreFuse totals shape.
     * All values use the canonical price shape.
     */
    private function format_totals( WC_Cart $cart ): array {
        return [
            'subtotal' => StoreFuse_Bridge_Format::price( (float) $cart->get_subtotal() ),
            'discount' => StoreFuse_Bridge_Format::price( (float) $cart->get_discount_total() ),
            'shipping' => StoreFuse_Bridge_Format::price( (float) $cart->get_shipping_total() ),
            'tax'      => StoreFuse_Bridge_Format::price( (float) $cart->get_total_tax() ),
            'total'    => StoreFuse_Bridge_Format::price( (float) $cart->get_total( 'edit' ) ),
        ];
    }
}
