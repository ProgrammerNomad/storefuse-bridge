<?php
defined( 'ABSPATH' ) || exit;

/**
 * Canonical data formatters.
 *
 * Every price and every image in every API response goes through this class.
 * No module ever returns a raw price string or a raw image URL.
 */
class StoreFuse_Bridge_Format {

    // ── Price ───

    /**
     * Format a monetary value into the canonical price shape.
     *
     * @param float|string|null $raw
     * @return array{ raw: float, formatted: string, currency: string, symbol: string }
     */
    public static function price( float|string|null $raw ): array {
        $amount = (float) ( $raw ?? 0 );
        return [
            'raw'       => $amount,
            'formatted' => strip_tags( wc_price( $amount ) ),
            'currency'  => get_woocommerce_currency(),
            'symbol'    => get_woocommerce_currency_symbol(),
        ];
    }

    // ── Image ───

    /**
     * Format a WordPress attachment into the canonical image shape.
     *
     * @param int|null $attachment_id
     * @return array{ url: string|null, alt: string, width: int|null, height: int|null, srcset: string[] }
     */
    public static function image( ?int $attachment_id ): array {
        if ( ! $attachment_id ) {
            return [
                'url'    => wc_placeholder_img_src( 'woocommerce_single' ),
                'alt'    => '',
                'width'  => null,
                'height' => null,
                'srcset' => [],
            ];
        }

        $url        = wp_get_attachment_image_url( $attachment_id, 'full' );
        $meta       = wp_get_attachment_metadata( $attachment_id );
        $srcset_raw = wp_get_attachment_image_srcset( $attachment_id, 'full' );

        return [
            'url'    => $url ?: null,
            'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'width'  => isset( $meta['width'] )  ? (int) $meta['width']  : null,
            'height' => isset( $meta['height'] ) ? (int) $meta['height'] : null,
            'srcset' => $srcset_raw
                ? array_values( array_filter( array_map( 'trim', explode( ',', $srcset_raw ) ) ) )
                : [],
        ];
    }

    // ── Date ────

    /**
     * Convert a WordPress date string to ISO 8601 / RFC 3339.
     * Returns null on empty/invalid input.
     */
    public static function date( ?string $wp_date ): ?string {
        if ( empty( $wp_date ) || $wp_date === '0000-00-00 00:00:00' ) {
            return null;
        }
        try {
            return ( new DateTime( $wp_date ) )->format( DateTime::ATOM );
        } catch ( Exception ) {
            return null;
        }
    }

    // ── Product 

    /**
     * Normalise a WC_Product into the StoreFuse product shape.
     * Used by the Products module and any place that embeds a product object.
     */
    public static function product( WC_Product $product ): array {
        // Images
        $images       = [];
        $gallery_ids  = $product->get_gallery_image_ids();
        $main_id      = $product->get_image_id();

        if ( $main_id ) {
            $images[] = self::image( (int) $main_id );
        }
        foreach ( $gallery_ids as $gid ) {
            $images[] = self::image( (int) $gid );
        }
        if ( empty( $images ) ) {
            $images[] = self::image( null );
        }

        // Categories
        $categories = [];
        $cat_terms  = get_the_terms( $product->get_id(), 'product_cat' );
        if ( is_array( $cat_terms ) ) {
            foreach ( $cat_terms as $term ) {
                $categories[] = [
                    'id'   => (string) $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        // Tags
        $tags      = [];
        $tag_terms = get_the_terms( $product->get_id(), 'product_tag' );
        if ( is_array( $tag_terms ) ) {
            foreach ( $tag_terms as $term ) {
                $tags[] = [
                    'id'   => (string) $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        // Attributes
        $attributes = [];
        foreach ( $product->get_attributes() as $attr ) {
            if ( ! $attr->get_visible() ) {
                continue;
            }
            $attributes[] = [
                'name'    => wc_attribute_label( $attr->get_name(), $product ),
                'slug'    => $attr->get_name(),
                'options' => $attr->is_taxonomy()
                    ? wc_get_product_terms( $product->get_id(), $attr->get_name(), [ 'fields' => 'names' ] )
                    : $attr->get_options(),
            ];
        }

        // Variations (for variable products)
        $variations = [];
        if ( $product->is_type( 'variable' ) ) {
            /** @var WC_Product_Variable $product */
            foreach ( $product->get_available_variations() as $variation_data ) {
                $variation = wc_get_product( $variation_data['variation_id'] );
                if ( ! $variation ) {
                    continue;
                }
                $variations[] = [
                    'id'           => (string) $variation->get_id(),
                    'sku'          => $variation->get_sku(),
                    'attributes'   => $variation_data['attributes'],
                    'price'        => self::price( $variation->get_price() ),
                    'stock_status' => $variation->get_stock_status(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'image'        => self::image( (int) $variation->get_image_id() ?: null ),
                ];
            }
        }

        // Sale end date (from _sale_price_dates_to meta)
        $sale_ends_at = null;
        $sale_end_ts  = $product->get_date_on_sale_to();
        if ( $sale_end_ts ) {
            $sale_ends_at = $sale_end_ts->date( DateTime::ATOM );
        }

        return [
            'id'                => (string) $product->get_id(),
            'slug'              => $product->get_slug(),
            'name'              => $product->get_name(),
            'description'       => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price'             => self::price( $product->get_price() ),
            'regular_price'     => self::price( $product->get_regular_price() ),
            'sale_price'        => $product->is_on_sale() ? self::price( $product->get_sale_price() ) : null,
            'on_sale'           => $product->is_on_sale(),
            'sale_ends_at'      => $sale_ends_at,
            'stock_status'      => $product->get_stock_status(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'sku'               => $product->get_sku(),
            'type'              => $product->get_type(),
            'images'            => $images,
            'categories'        => $categories,
            'tags'              => $tags,
            'attributes'        => $attributes,
            'variations'        => $variations,
            'average_rating'    => $product->get_average_rating(),
            'rating_count'      => $product->get_rating_count(),
        ];
    }

    // ── Category ─────────────────────────────────────────────────────────────

    /**
     * Normalise a WP_Term (product_cat) into the StoreFuse category shape.
     */
    public static function category( WP_Term $term ): array {
        $thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );

        return [
            'id'            => (string) $term->term_id,
            'name'          => $term->name,
            'slug'          => $term->slug,
            'description'   => $term->description,
            'image'         => self::image( $thumbnail_id ?: null ),
            'product_count' => (int) $term->count,
            'parent'        => $term->parent ? (string) $term->parent : null,
        ];
    }

    // ── Order ───

    /**
     * Normalise a WC order into the full StoreFuse order shape.
     * Used by the Checkout (order confirmation) and Orders (account history) modules.
     */
    public static function order( WC_Abstract_Order $order ): array {
        $line_items = [];

        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product_id   = $item->get_product_id();
            $product      = $item->get_product();
            $product_slug = get_post_field( 'post_name', $product_id );
            $thumbnail    = ( $product instanceof WC_Product )
                ? self::image( (int) $product->get_image_id() )
                : null;

            $line_items[] = [
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => self::price( (float) $item->get_subtotal() ),
                'total'    => self::price( (float) $item->get_total() ),
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
            'date_created'         => self::date(
                $order->get_date_created()
                    ? $order->get_date_created()->date( 'Y-m-d H:i:s' )
                    : ''
            ),
            'currency'             => $order->get_currency(),
            'billing'              => self::order_address( $order, 'billing' ),
            'shipping'             => self::order_address( $order, 'shipping' ),
            'items'                => $line_items,
            'totals'               => [
                'subtotal' => self::price( (float) $order->get_subtotal() ),
                'discount' => self::price( (float) $order->get_discount_total() ),
                'shipping' => self::price( (float) $order->get_shipping_total() ),
                'tax'      => self::price( (float) $order->get_total_tax() ),
                'total'    => self::price( (float) $order->get_total( 'edit' ) ),
            ],
            'payment_method'       => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'customer_note'        => $order->get_customer_note(),
            'is_paid'              => $order->is_paid(),
        ], $order );
    }

    /**
     * Normalise billing or shipping address from a WC order.
     *
     * @param string $type 'billing'|'shipping'
     */
    public static function order_address( WC_Abstract_Order $order, string $type ): array {
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
     * Compact order shape for list views (order history page).
     */
    public static function order_summary( WC_Abstract_Order $order ): array {
        return [
            'id'           => $order->get_id(),
            'number'       => $order->get_order_number(),
            'status'       => $order->get_status(),
            'date_created' => self::date(
                $order->get_date_created()
                    ? $order->get_date_created()->date( 'Y-m-d H:i:s' )
                    : ''
            ),
            'total'        => self::price( (float) $order->get_total( 'edit' ) ),
            'item_count'   => $order->get_item_count(),
            'billing_name' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
        ];
    }
}
