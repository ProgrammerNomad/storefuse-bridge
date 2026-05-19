<?php
/**
 * Uninstall handler.
 *
 * Called when the plugin is deleted from WordPress admin.
 * Removes all plugin data from wp_options.
 *
 * Note: this file is executed in the WordPress context, not via the autoloader,
 * so we load the settings class manually.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Load the settings class so we can call its delete method
$settings_file = plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
if ( file_exists( $settings_file ) ) {
    require_once $settings_file;
    StoreFuse_Bridge_Settings::delete_all();
}

// Delete standalone options
delete_option( 'storefuse_bridge_version' );
delete_option( 'storefuse_bridge_activated_at' );
delete_option( 'storefuse_bridge_cache_keys' );

// Flush any remaining transients using the sfb_ prefix
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_sfb_%'
        OR option_name LIKE '_transient_timeout_sfb_%'"
);
