<?php
defined( 'ABSPATH' ) || exit;

/**
 * Centralised error factory.
 *
 * All errors returned by StoreFuse Bridge use the same envelope:
 * {
 *   "schema":      "storefuse.error.v1",
 *   "api_version": "1.0.0",
 *   "error": {
 *     "code":    "snake_case_string",
 *     "message": "Human-readable message.",
 *     "status":  404
 *   }
 * }
 *
 * The 'status' field is included in the body (not only as HTTP status code)
 * so clients that do not inspect HTTP codes still get the correct context.
 */
class StoreFuse_Bridge_Errors {

    // ── Factory ──────────────────────────────────────────────────────────────

    private static function make( string $code, string $message, int $status ): WP_REST_Response {
        return new WP_REST_Response(
            [
                'schema'      => 'storefuse.error.v1',
                'api_version' => STOREFUSE_BRIDGE_VERSION,
                'error'       => [
                    'code'    => $code,
                    'message' => $message,
                    'status'  => $status,
                ],
            ],
            $status
        );
    }

    // ── 400 Bad Request ──────────────────────────────────────────────────────

    public static function validation_error( string $message ): WP_REST_Response {
        return self::make( 'validation_error', $message, 400 );
    }

    public static function invalid_nonce(): WP_REST_Response {
        return self::make( 'invalid_nonce', 'Security token missing or invalid.', 403 );
    }

    public static function invalid_email(): WP_REST_Response {
        return self::make( 'invalid_email', 'A valid email address is required.', 400 );
    }

    // ── 401 Unauthorised ─────────────────────────────────────────────────────

    public static function not_authenticated(): WP_REST_Response {
        return self::make( 'not_authenticated', 'You must be logged in to access this resource.', 401 );
    }

    public static function invalid_credentials(): WP_REST_Response {
        return self::make( 'invalid_credentials', 'Email address or password is incorrect.', 401 );
    }

    // ── 403 Forbidden ────────────────────────────────────────────────────────

    public static function forbidden(): WP_REST_Response {
        return self::make( 'forbidden', 'You do not have permission to access this resource.', 403 );
    }

    // ── 404 Not Found ────────────────────────────────────────────────────────

    public static function product_not_found(): WP_REST_Response {
        return self::make( 'product_not_found', 'Product not found.', 404 );
    }

    public static function category_not_found(): WP_REST_Response {
        return self::make( 'category_not_found', 'Category not found.', 404 );
    }

    public static function order_not_found(): WP_REST_Response {
        return self::make( 'order_not_found', 'Order not found.', 404 );
    }

    public static function cart_item_not_found(): WP_REST_Response {
        return self::make( 'cart_item_not_found', 'Cart item not found.', 404 );
    }

    public static function user_not_found(): WP_REST_Response {
        return self::make( 'user_not_found', 'User not found.', 404 );
    }

    // ── 409 Conflict ─────────────────────────────────────────────────────────

    public static function email_already_registered(): WP_REST_Response {
        return self::make( 'email_already_registered', 'An account with this email address already exists.', 409 );
    }

    // ── 410 Gone ─────────────────────────────────────────────────────────────

    public static function out_of_stock(): WP_REST_Response {
        return self::make( 'out_of_stock', 'One or more items in your cart are out of stock.', 410 );
    }

    // ── 422 Unprocessable ────────────────────────────────────────────────────

    public static function coupon_invalid(): WP_REST_Response {
        return self::make( 'coupon_invalid', 'This coupon code is not valid.', 422 );
    }

    public static function coupon_expired(): WP_REST_Response {
        return self::make( 'coupon_expired', 'This coupon has expired.', 422 );
    }

    public static function checkout_failed( string $message = '' ): WP_REST_Response {
        return self::make( 'checkout_failed', $message ?: 'Checkout could not be completed.', 422 );
    }

    // ── 500 Server Error ─────────────────────────────────────────────────────

    public static function server_error( string $message = '' ): WP_REST_Response {
        return self::make( 'server_error', $message ?: 'An unexpected error occurred.', 500 );
    }
}
