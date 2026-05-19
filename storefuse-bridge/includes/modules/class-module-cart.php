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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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
                'permission_callback' => '__return_true',
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
                'permission_callback' => '__return_true',
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
        $this->ensure_cart();

        return $this->cart_response();
    }

    /**
     * POST /cart/add
     *
     * Add a product or product variation to the cart.
     * Pass `variation_id` and a `variation` map for variable products.
     */
    public function add_item( WP_REST_Request $request ): WP_REST_Response {
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

        $product_id   = (int) $request->get_param( 'product_id' );
        $variation_id = (int) $request->get_param( 'variation_id' );
        $quantity     = max( 1, (int) $request->get_param( 'quantity' ) );
        $variation    = (array) $request->get_param( 'variation' );

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->exists() ) {
            return StoreFuse_Bridge_Errors::product_not_found();
        }

        if ( ! $product->is_in_stock() ) {
            return StoreFuse_Bridge_Errors::out_of_stock();
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
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

        $cart_item_key = $request->get_param( 'cart_item_key' );
        $quantity      = (int) $request->get_param( 'quantity' );

        if ( ! isset( WC()->cart->get_cart()[ $cart_item_key ] ) ) {
            return StoreFuse_Bridge_Errors::cart_item_not_found();
        }

        if ( $quantity <= 0 ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        } else {
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
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

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
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

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
        $nonce_error = $this->check_cart_nonce( $request );
        if ( $nonce_error ) {
            return $nonce_error;
        }

        $this->ensure_cart();

        $code = wc_format_coupon_code( $request->get_param( 'code' ) );

        if ( ! WC()->cart->has_discount( $code ) ) {
            return StoreFuse_Bridge_Errors::validation_error( 'This coupon is not applied to your cart.' );
        }

        WC()->cart->remove_coupon( $code );
        WC()->cart->calculate_totals();

        return $this->cart_response();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Validate the X-WC-Nonce header on cart write operations.
     *
     * Returns null when nonce is valid, WP_REST_Response error when invalid.
     * Checked at the start of every write handler to ensure our StoreFuse
     * error envelope is returned (not WordPress's default 403).
     */
    private function check_cart_nonce( WP_REST_Request $request ): ?WP_REST_Response {
        $nonce = $request->get_header( 'X-WC-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wc_store_api' ) ) {
            return StoreFuse_Bridge_Errors::invalid_nonce();
        }
        return null;
    }

    /**
     * Ensure WooCommerce session and cart are initialised before access.
     *
     * WooCommerce typically initialises the cart on the `wp` hook which does
     * not fire during REST requests. wc_load_cart() handles the full
     * initialisation chain (session, customer, cart) safely.
     */
    private function ensure_cart(): void {
        if ( ! WC()->cart ) {
            wc_load_cart();
        }
    }

    /**
     * Build the standard cart response with session headers.
     */
    private function cart_response(): WP_REST_Response {
        $response = $this->success( $this->format_cart(), 'storefuse.cart.v1' );
        $response = StoreFuse_Bridge_Session::set_cart_token_header( $response );
        return StoreFuse_Bridge_Response::with_no_store( $response );
    }

    /**
     * Normalise the WooCommerce cart into the StoreFuse cart shape.
     */
    private function format_cart(): array {
        $cart = WC()->cart;

        return apply_filters( 'storefuse_bridge_cart_data', [
            'items'          => $this->format_cart_items( $cart->get_cart() ),
            'coupons'        => $this->format_coupons( $cart->get_applied_coupons() ),
            'totals'         => $this->format_totals( $cart ),
            'item_count'     => $cart->get_cart_contents_count(),
            'needs_shipping' => $cart->needs_shipping(),
            'is_empty'       => $cart->is_empty(),
        ] );
    }

    /**
     * Normalise WC cart items into the StoreFuse cart item shape.
     */
    private function format_cart_items( array $cart_items ): array {
        $items = [];

        foreach ( $cart_items as $cart_item_key => $item ) {
            /** @var WC_Product|false $product */
            $product = $item['data'] ?? null;
            if ( ! $product instanceof WC_Product ) {
                continue;
            }

            // Humanise variation attribute labels
            // e.g. 'attribute_pa_color' => 'Red'  becomes  'Color' => 'Red'
            $variation_attrs = [];
            if ( ! empty( $item['variation'] ) ) {
                foreach ( $item['variation'] as $attr_key => $attr_value ) {
                    $label                       = wc_attribute_label(
                        str_replace( 'attribute_', '', $attr_key )
                    );
                    $variation_attrs[ $label ] = $attr_value;
                }
            }

            $parent_slug = get_post_field( 'post_name', $item['product_id'] );

            $items[] = apply_filters(
                'storefuse_bridge_cart_item',
                [
                    'key'          => $cart_item_key,
                    'product_id'   => $item['product_id'],
                    'variation_id' => $item['variation_id'],
                    'name'         => $product->get_name(),
                    'slug'         => $parent_slug,
                    'href'         => '/product/' . $parent_slug,
                    'quantity'     => $item['quantity'],
                    'thumbnail'    => StoreFuse_Bridge_Format::image( (int) $product->get_image_id() ),
                    'price'        => StoreFuse_Bridge_Format::price( (float) $product->get_price() ),
                    'subtotal'     => StoreFuse_Bridge_Format::price( (float) $item['line_subtotal'] ),
                    'variation'    => $variation_attrs,
                    'is_in_stock'  => $product->is_in_stock(),
                ],
                $item
            );
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
