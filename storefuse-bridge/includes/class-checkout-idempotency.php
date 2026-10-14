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
            return StoreFuse_Bridge_Errors::validation_error( 'Idempotency-Key is required for checkout.' );
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
        $payload = [
            'billing'      => self::normalize_address( $billing ),
            'shipping'     => self::normalize_address( $shipping ),
            'ship_diff'    => $ship_to_different,
            'payment'      => $payment_method,
            'shipping_m'   => array_values( $shipping_methods ),
            'notes'        => $order_notes,
        ];

        return hash( 'sha256', wp_json_encode( $payload ) );
    }

    /**
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private static function normalize_address( array $address ): array {
        $out  = [];
        foreach ( $address as $k => $value ) {
            if ( is_scalar( $value ) && (string) $value !== '' ) {
                $out[ $k ] = (string) $value;
            }
        }
        ksort( $out );
        return $out;
    }

    /**
     * @return array{status:int,body:array<string,mixed>}|null|'conflict'
     */
    public static function get_cached( string $storage_key, string $fingerprint ): array|string|null {
        // A second lookup after lock acquisition must observe another request's write.
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $storage_key, 'options' );
            wp_cache_delete( 'notoptions', 'options' );
        }
        $cached = get_option( $storage_key, false );
        if ( $cached === false ) {
            $cached = get_transient( $storage_key );
        }
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
        // This record is scoped to the authenticated user or guest session.
        $safe_body = $body;
        update_option(
            $storage_key,
            [
                'status'      => $status,
                'body'        => $safe_body,
                'fingerprint' => $fingerprint,
                'updated_at'  => time(),
            ],
            false
        );
    }

    /** @var array<string, string> Locks owned by this PHP request. */
    private static array $owned_locks = [];

    public static function acquire_lock( string $lock_key ): bool {
        global $wpdb;
        // MySQL advisory locks are connection-owned and released on disconnect.
        // Never fall back to another backend on contention or a database error.
        if ( isset( self::$owned_locks[ $lock_key ] ) ) {
            return false;
        }
        $filtered = apply_filters( 'storefuse_bridge_idempotency_acquire', null, $lock_key );
        if ( is_bool( $filtered ) ) {
            if ( $filtered ) {
                self::$owned_locks[ $lock_key ] = 'extension';
            }
            return $filtered;
        }
        if ( isset( $wpdb ) ) {
            $name = 'sfb:' . hash( 'sha256', $wpdb->prefix . $lock_key );
            $name = substr( $name, 0, 64 );
            $acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) );
            if ( (string) $acquired !== '1' ) {
                return false;
            }
            self::$owned_locks[ $lock_key ] = $name;
            return true;
        }
        // Isolated test environments only. WordPress always supplies wpdb.
        $token = bin2hex( random_bytes( 16 ) );
        if ( ! add_option( $lock_key, $token, '', 'no' ) ) {
            return false;
        }
        self::$owned_locks[ $lock_key ] = $token;
        return true;
    }

    public static function release_lock( string $lock_key ): void {
        global $wpdb;
        $owner = self::$owned_locks[ $lock_key ] ?? null;
        if ( $owner === null ) {
            return;
        }
        unset( self::$owned_locks[ $lock_key ] );
        if ( $owner === 'extension' ) {
            do_action( 'storefuse_bridge_idempotency_release', $lock_key );
        } elseif ( isset( $wpdb ) ) {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $owner ) );
        } elseif ( get_option( $lock_key ) === $owner ) {
            delete_option( $lock_key );
        }
    }

    /** Kept for activation compatibility; advisory locks require no table. */
    public static function ensure_lock_table(): void {}
}
