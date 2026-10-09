<?php
defined( 'ABSPATH' ) || exit;

/**
 * Versioned upgrade callbacks on plugin load.
 */
class StoreFuse_Bridge_Migrations {

    public static function run(): void {
        $stored = (string) get_option( 'storefuse_bridge_version', '0.1.0' );

        if ( version_compare( $stored, STOREFUSE_BRIDGE_VERSION, '>=' ) ) {
            return;
        }

        if ( version_compare( $stored, '0.2.0', '<' ) ) {
            self::upgrade_to_0_2_0();
        }

        if ( version_compare( $stored, '1.0.2', '<' ) ) {
            self::upgrade_to_1_0_2();
        }

        update_option( 'storefuse_bridge_version', STOREFUSE_BRIDGE_VERSION, false );
    }

    /**
     * Schema required on activation and upgrades (idempotent).
     */
    public static function install_schema(): void {
        self::upgrade_to_1_0_2();
    }

    private static function upgrade_to_0_2_0(): void {
        // Drop legacy cache key tracking option (flush uses SQL prefix delete).
        delete_option( 'storefuse_bridge_cache_keys' );
    }

    private static function upgrade_to_1_0_2(): void {
        StoreFuse_Bridge_Checkout_Idempotency::ensure_lock_table();
    }
}
