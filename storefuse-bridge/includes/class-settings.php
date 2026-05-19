<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin settings - thin wrapper around wp_options.
 *
 * All plugin-managed settings live under the 'storefuse_bridge_settings' option key
 * as a single serialised array. Module enabled/disabled flags use the same key.
 */
class StoreFuse_Bridge_Settings {

    private const OPTION_KEY = 'storefuse_bridge_settings';

    /** In-memory cache so we don't hit the DB multiple times per request. */
    private static ?array $cache = null;

    /**
     * Read a setting value.
     *
     * @param string $key     Dot-notation supported (e.g. 'header.announcement_bar_text')
     * @param mixed  $default Returned when key is missing
     */
    public static function get( string $key, mixed $default = null ): mixed {
        $settings = self::all();

        // Support dot-notation
        $parts = explode( '.', $key );
        $value = $settings;
        foreach ( $parts as $part ) {
            if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
                return $default;
            }
            $value = $value[ $part ];
        }
        return $value;
    }

    /**
     * Write one or more settings.
     *
     * @param array $values Flat or nested array of key => value pairs
     */
    public static function set( array $values ): void {
        $settings = self::all();
        foreach ( $values as $key => $value ) {
            $settings[ $key ] = $value;
        }
        update_option( self::OPTION_KEY, $settings );
        self::$cache = $settings;
    }

    /**
     * Return all settings as a flat array.
     */
    public static function all(): array {
        if ( self::$cache === null ) {
            self::$cache = (array) get_option( self::OPTION_KEY, [] );
        }
        return self::$cache;
    }

    /**
     * Delete all plugin settings. Called on uninstall.
     */
    public static function delete_all(): void {
        delete_option( self::OPTION_KEY );
        self::$cache = null;
    }

    /**
     * Reset the in-memory cache (used in tests or after direct DB manipulation).
     */
    public static function reset_cache(): void {
        self::$cache = null;
    }

    /**
     * Default values for first-time installs.
     */
    public static function defaults(): array {
        return [
            // Announcement bar
            'announcement_bar_enabled'  => true,
            'announcement_bar_text'     => '',
            'announcement_bar_bg_color' => '#E85D04',
            'announcement_bar_link'     => '',

            // Store policies
            'return_policy_days'             => 7,
            'free_shipping_threshold_label'  => '',
            'free_shipping_threshold_amount' => 0,

            // Trust badges (JSON-encoded array of objects)
            'trust_badges' => [],

            // Social links
            'social_instagram' => '',
            'social_facebook'  => '',
            'social_twitter'   => '',
            'social_youtube'   => '',
            'social_pinterest' => '',
            'social_whatsapp'  => '',

            // Module toggles (all on by default)
            'module_products_enabled'   => true,
            'module_categories_enabled' => true,
            'module_search_enabled'     => true,
            'module_cart_enabled'       => true,
            'module_checkout_enabled'   => true,
            'module_content_enabled'    => true,
            'module_webhooks_enabled'   => false,
        ];
    }
}
