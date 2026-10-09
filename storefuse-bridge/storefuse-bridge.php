<?php
/**
 * Plugin Name:       StoreFuse Bridge
 * Plugin URI:        https://github.com/ProgrammerNomad/storefuse-bridge
 * Description:       The official WordPress/WooCommerce companion plugin for StoreFuse. Exposes a clean, versioned REST API namespace for headless storefronts.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            NomadProgrammer
 * Author URI:        https://github.com/ProgrammerNomad
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       storefuse-bridge
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to: 9.9
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ───

define( 'STOREFUSE_BRIDGE_VERSION',  '1.0.0' );
define( 'STOREFUSE_BRIDGE_PATH',     plugin_dir_path( __FILE__ ) );
define( 'STOREFUSE_BRIDGE_URL',      plugin_dir_url( __FILE__ ) );
define( 'STOREFUSE_BRIDGE_BASENAME', plugin_basename( __FILE__ ) );
define( 'STOREFUSE_BRIDGE_MIN_WP',   '6.0' );
define( 'STOREFUSE_BRIDGE_MIN_WC',   '7.0' );
define( 'STOREFUSE_BRIDGE_MIN_PHP',  '8.0' );
define(
    'STOREFUSE_BRIDGE_DOCS_URL',
    'https://github.com/ProgrammerNomad/storefuse-bridge/blob/main/docs/README.md'
);

// ── Autoloader ─

spl_autoload_register( function ( string $class ): void {
    // Only handle StoreFuse_Bridge_* classes
    if ( strpos( $class, 'StoreFuse_Bridge' ) !== 0 ) {
        return;
    }

    /*
     * Naming convention → file path:
     *   StoreFuse_Bridge            → includes/class-plugin.php
     *   StoreFuse_Bridge_Format     → includes/class-format.php
     *   StoreFuse_Bridge_Module_Status → includes/modules/class-module-status.php
     */
    $suffix = substr( $class, strlen( 'StoreFuse_Bridge' ) );
    $suffix = strtolower( str_replace( '_', '-', ltrim( $suffix, '_' ) ) );

    if ( strpos( $suffix, 'module-' ) !== false || preg_match( '/^module-/', $suffix ) ) {
        $file = STOREFUSE_BRIDGE_PATH . 'includes/modules/class-' . $suffix . '.php';
    } elseif ( $suffix === '' ) {
        $file = STOREFUSE_BRIDGE_PATH . 'includes/class-plugin.php';
    } else {
        $file = STOREFUSE_BRIDGE_PATH . 'includes/class-' . $suffix . '.php';
    }

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ── Activation / Deactivation / Uninstall ────────────────────────────────────

register_activation_hook( __FILE__, 'storefuse_bridge_activate' );
register_deactivation_hook( __FILE__, 'storefuse_bridge_deactivate' );

function storefuse_bridge_activate(): void {
    if ( ! function_exists( 'is_woocommerce_active' ) ) {
        // Check manually
        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ), true ) ) {
            deactivate_plugins( STOREFUSE_BRIDGE_BASENAME );
            wp_die(
                esc_html__( 'StoreFuse Bridge requires WooCommerce to be installed and active.', 'storefuse-bridge' ),
                esc_html__( 'Plugin Activation Error', 'storefuse-bridge' ),
                [ 'back_link' => true ]
            );
        }
    }

    // Store activation version for future migration checks
    update_option( 'storefuse_bridge_version', STOREFUSE_BRIDGE_VERSION );
    update_option( 'storefuse_bridge_activated_at', current_time( 'mysql' ) );

    set_transient( 'storefuse_bridge_show_setup_notice', 1, WEEK_IN_SECONDS );

    // Flush rewrite rules so REST routes register cleanly
    flush_rewrite_rules();
}

function storefuse_bridge_deactivate(): void {
    // Flush transient cache on deactivation
    StoreFuse_Bridge_Cache::flush_all();
    flush_rewrite_rules();
}

// Uninstall is handled via uninstall.php (keeps activation hook clean)

// ── Admin notice: WooCommerce missing ────────────────────────────────────────

add_action( 'admin_notices', function (): void {
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: WooCommerce plugin link */
            esc_html__( 'StoreFuse Bridge requires %s to be installed and active.', 'storefuse-bridge' ),
            '<strong>WooCommerce</strong>'
        );
        echo '</p></div>';
        return;
    }

    if ( ! get_transient( 'storefuse_bridge_show_setup_notice' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    $dash_url = admin_url( 'admin.php?page=storefuse-bridge' );
    echo '<div class="notice notice-info is-dismissible" data-sfb-setup-notice="1"><p>';
    printf(
        wp_kses_post(
            /* translators: %s: dashboard URL */
            __( '<strong>StoreFuse Bridge is active.</strong> Complete the setup checklist on the <a href="%s">Dashboard</a> to connect your headless storefront (web or mobile).', 'storefuse-bridge' )
        ),
        esc_url( $dash_url )
    );
    echo '</p></div>';
} );

// ── Bootstrap ───

add_action( 'plugins_loaded', function (): void {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    StoreFuse_Bridge_Migrations::run();
    StoreFuse_Bridge::instance()->init();
}, 5 );

// ── Storefront integrations ───────────────────────────────────────────────────

/**
 * Redirect password reset emails to the storefront reset page when configured.
 *
 * Uses `storefront_url` and `storefront_reset_path` from StoreFuse Bridge settings.
 * Companion plugins or themes may override via `storefuse_bridge_password_reset_url`.
 */
add_filter( 'storefuse_bridge_password_reset_url', function ( string $wp_url, WP_User $user, string $key ): string {
    $storefront_url = rtrim( (string) StoreFuse_Bridge_Settings::get( 'storefront_url', '' ), '/' );

    // Legacy option (pre-settings UI); remove once no stores rely on it.
    if ( ! $storefront_url ) {
        $storefront_url = rtrim( (string) get_option( 'storefuse_storefront_url', '' ), '/' );
    }

    if ( ! $storefront_url ) {
        return $wp_url;
    }

    $path = (string) StoreFuse_Bridge_Settings::get( 'storefront_reset_path', '/reset-password' );
    $path = '/' . ltrim( $path, '/' );

    return add_query_arg(
        [
            'key'   => rawurlencode( $key ),
            'login' => rawurlencode( $user->user_login ),
        ],
        $storefront_url . $path
    );
}, 10, 3 );
