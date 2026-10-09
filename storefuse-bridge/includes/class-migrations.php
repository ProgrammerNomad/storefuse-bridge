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

        update_option( 'storefuse_bridge_version', STOREFUSE_BRIDGE_VERSION, false );
    }

    private static function upgrade_to_0_2_0(): void {
        // Drop legacy cache key tracking option (flush uses SQL prefix delete).
        delete_option( 'storefuse_bridge_cache_keys' );
    }
}
