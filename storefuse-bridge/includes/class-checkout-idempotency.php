<?php
defined( 'ABSPATH' ) || exit;

/**
 * Scoped checkout idempotency (session/user + request fingerprint).
 */
class StoreFuse_Bridge_Checkout_Idempotency {

    public const KEY_MIN = 8;
    public const KEY_MAX = 128;
    public const LOCK_TTL = 120;

    /**
     * @return true|WP_REST_Response
     */
    public static function validate_key( string $key ): bool|WP_REST_Response {
        if ( $key === '' ) {
            return true;
        }
        if ( strlen( $key ) < self::KEY_MIN || strlen( $key ) > self::KEY_MAX ) {
            return StoreFuse_Bridge_Errors::validation_error(
                sprintf( 'Idempotency-Key must be between %d and %d characters.', self::KEY_MIN, self::KEY_MAX )
            );
        }
        if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $key ) ) {
            return StoreFuse_Bridge_Errors::validation_error(
                'Idempotency-Key may only contain letters, numbers, underscores, and hyphens.'
            );
        }
        return true;
    }

    public static function scope(): string {
        if ( is_user_logged_in() ) {
            return 'user:' . (string) get_current_user_id();
        }
        $customer_id = '';
        if ( WC()->session instanceof WC_Session ) {
            $customer_id = (string) WC()->session->get_customer_id();
        }
        if ( $customer_id === '' ) {
            $customer_id = 'guest:anonymous';
        }
        return 'session:' . $customer_id;
    }

    public static function storage_key( string $scope, string $idempotency_key ): string {
        return 'sfb_idem_' . md5( $scope . '|' . $idempotency_key );
    }

    public static function lock_key( string $scope, string $idempotency_key ): string {
        return 'sfb_idem_lock_' . md5( $scope . '|' . $idempotency_key );
    }

    /**
     * @param array<string, mixed> $billing
     * @param array<string, mixed> $shipping
     * @param array<int, string>   $shipping_methods
     */
    public static function fingerprint(
        array $billing,
        array $shipping,
        bool $ship_to_different,
        string $payment_method,
        array $shipping_methods,
        string $order_notes
    ): string {
        $cart_lines = [];
        if ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $key => $line ) {
                $cart_lines[] = [
                    'key'  => $key,
                    'pid'  => (int) ( $line['product_id'] ?? 0 ),
                    'vid'  => (int) ( $line['variation_id'] ?? 0 ),
                    'qty'  => (int) ( $line['quantity'] ?? 0 ),
                ];
            }
        }
        usort(
            $cart_lines,
            static function ( array $a, array $b ): int {
                return strcmp( (string) $a['key'], (string) $b['key'] );
            }
        );

        $payload = [
            'billing'      => self::normalize_address( $billing ),
            'shipping'     => self::normalize_address( $shipping ),
            'ship_diff'    => $ship_to_different,
            'payment'      => $payment_method,
            'shipping_m'   => array_values( $shipping_methods ),
            'notes'        => $order_notes,
            'cart'         => $cart_lines,
        ];

        return hash( 'sha256', wp_json_encode( $payload ) );
    }

    /**
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private static function normalize_address( array $address ): array {
        $keys = [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone' ];
        $out  = [];
        foreach ( $keys as $k ) {
            $out[ $k ] = isset( $address[ $k ] ) ? (string) $address[ $k ] : '';
        }
        return $out;
    }

    /**
     * @return array{status:int,body:array<string,mixed>}|null|'conflict'
     */
    public static function get_cached( string $storage_key, string $fingerprint ): array|string|null {
        $cached = get_transient( $storage_key );
        if ( ! is_array( $cached ) || ! isset( $cached['body'], $cached['status'], $cached['fingerprint'] ) ) {
            return null;
        }
        if ( ! hash_equals( (string) $cached['fingerprint'], $fingerprint ) ) {
            return 'conflict';
        }
        return [
            'status' => (int) $cached['status'],
            'body'   => (array) $cached['body'],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function store( string $storage_key, string $fingerprint, int $status, array $body ): void {
        $safe_body = $body;
        if ( isset( $safe_body['data'] ) && is_array( $safe_body['data'] ) ) {
            unset( $safe_body['data']['order_key'] );
        }
        set_transient(
            $storage_key,
            [
                'status'      => $status,
                'body'        => $safe_body,
                'fingerprint' => $fingerprint,
            ],
            DAY_IN_SECONDS
        );
    }

    public static function acquire_lock( string $lock_key ): bool {
        if ( get_transient( $lock_key ) ) {
            return false;
        }
        set_transient( $lock_key, 1, self::LOCK_TTL );
        return true;
    }

    public static function release_lock( string $lock_key ): void {
        delete_transient( $lock_key );
    }
}
