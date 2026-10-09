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
        $filtered = apply_filters( 'storefuse_bridge_idempotency_acquire', null, $lock_key );
        if ( is_bool( $filtered ) ) {
            return $filtered;
        }

        if ( self::acquire_lock_db( $lock_key ) ) {
            return true;
        }

        return self::acquire_lock_option( $lock_key );
    }

    public static function release_lock( string $lock_key ): void {
        self::release_lock_db( $lock_key );
        delete_option( self::lock_option_name( $lock_key ) );
    }

    private static function lock_option_name( string $lock_key ): string {
        return 'sfb_idem_lock_' . md5( $lock_key );
    }

    private static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'sfb_idempotency_locks';
    }

    public static function ensure_lock_table(): void {
        global $wpdb;
        if ( ! isset( $wpdb ) ) {
            return;
        }

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE {$table} (
            lock_key varchar(64) NOT NULL,
            expires_at bigint unsigned NOT NULL,
            PRIMARY KEY  (lock_key)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private static function acquire_lock_db( string $lock_key ): bool {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'insert' ) ) {
            return false;
        }

        self::ensure_lock_table();

        $table   = self::table_name();
        $now     = time();
        $expires = $now + self::LOCK_TTL;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %d", $now ) );

        $inserted = $wpdb->insert(
            $table,
            [
                'lock_key'   => $lock_key,
                'expires_at' => $expires,
            ],
            [ '%s', '%d' ]
        );

        if ( $inserted !== false ) {
            return true;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
        $existing = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT expires_at FROM {$table} WHERE lock_key = %s LIMIT 1", $lock_key )
        );
        if ( $existing > $now ) {
            return false;
        }

        $wpdb->delete( $table, [ 'lock_key' => $lock_key ], [ '%s' ] );

        return $wpdb->insert(
            $table,
            [
                'lock_key'   => $lock_key,
                'expires_at' => $expires,
            ],
            [ '%s', '%d' ]
        ) !== false;
    }

    private static function release_lock_db( string $lock_key ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'delete' ) ) {
            return;
        }

        $table = self::table_name();
        $wpdb->delete( $table, [ 'lock_key' => $lock_key ], [ '%s' ] );
    }

    private static function acquire_lock_option( string $lock_key ): bool {
        $option  = self::lock_option_name( $lock_key );
        $now     = time();
        $expires = $now + self::LOCK_TTL;

        if ( false !== add_option( $option, (string) $expires, '', 'no' ) ) {
            return true;
        }

        $stored = get_option( $option );
        if ( is_numeric( $stored ) && (int) $stored > $now ) {
            return false;
        }

        delete_option( $option );

        return false !== add_option( $option, (string) $expires, '', 'no' );
    }
}
