<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/storefuse-bridge/' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_VERSION' ) ) {
    define( 'STOREFUSE_BRIDGE_VERSION', '1.0.2' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_BASENAME' ) ) {
    define( 'STOREFUSE_BRIDGE_BASENAME', 'storefuse-bridge/storefuse-bridge.php' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_URL' ) ) {
    define( 'STOREFUSE_BRIDGE_URL', 'https://example.test/wp-content/plugins/storefuse-bridge/' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_PATH' ) ) {
    define( 'STOREFUSE_BRIDGE_PATH', dirname( __DIR__ ) . '/storefuse-bridge/' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_MIN_WC' ) ) {
    define( 'STOREFUSE_BRIDGE_MIN_WC', '7.0' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_MIN_WP' ) ) {
    define( 'STOREFUSE_BRIDGE_MIN_WP', '6.0' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_MIN_PHP' ) ) {
    define( 'STOREFUSE_BRIDGE_MIN_PHP', '8.0' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 86400 );
}

/** @var array<string, mixed> */
$GLOBALS['_sfb_transients'] = $GLOBALS['_sfb_transients'] ?? [];

/** @var array<string, mixed> */
$GLOBALS['_sfb_options'] = $GLOBALS['_sfb_options'] ?? [];

/** @var array<string, callable> */
$GLOBALS['_sfb_filters'] = $GLOBALS['_sfb_filters'] ?? [];

/** @var array<string, array<array{mixed, int}>> */
$GLOBALS['_sfb_actions'] = $GLOBALS['_sfb_actions'] ?? [];
$GLOBALS['_sfb_menus']  = $GLOBALS['_sfb_menus'] ?? [];

if ( ! function_exists( 'apply_filters' ) ) {
    /**
     * @param mixed $value
     * @return mixed
     */
    function apply_filters( string $hook, $value, ...$args ) {
        if ( empty( $GLOBALS['_sfb_filters'][ $hook ] ) ) {
            return $value;
        }
        foreach ( $GLOBALS['_sfb_filters'][ $hook ] as $callback ) {
            $value = $callback( $value, ...$args );
        }
        return $value;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        unset( $priority, $accepted_args );
        $GLOBALS['_sfb_filters'][ $hook ][] = $callback;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        unset( $accepted_args );
        $GLOBALS['_sfb_actions'][ $hook ][] = [ $callback, $priority ];
        return true;
    }
}

if ( ! function_exists( 'add_menu_page' ) ) {
    function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', string $icon_url = '', $position = null ): string {
        $GLOBALS['_sfb_menus']['top'][] = [ $page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, $position ];
        return 'toplevel_page_' . $menu_slug;
    }
}

if ( ! function_exists( 'add_submenu_page' ) ) {
    function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', $position = null ): string {
        $GLOBALS['_sfb_menus']['sub'][] = [ $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback, $position ];
        return 'storefuse_page_' . $menu_slug;
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( string $key ) {
        return $GLOBALS['_sfb_transients'][ $key ] ?? false;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( string $key, $value, int $expiration ): bool {
        $GLOBALS['_sfb_transients'][ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( string $key ): bool {
        unset( $GLOBALS['_sfb_transients'][ $key ] );
        return true;
    }
}

if ( ! function_exists( 'add_option' ) ) {
    function add_option( string $option, $value, $deprecated = '', $autoload = 'yes' ): bool {
        if ( array_key_exists( $option, $GLOBALS['_sfb_options'] ) ) {
            return false;
        }
        $GLOBALS['_sfb_options'][ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, $default = false ) {
        return $GLOBALS['_sfb_options'][ $option ] ?? $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $key, $value, $autoload = null ): bool {
        $GLOBALS['_sfb_options'][ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( string $type ): string {
        return '2026-10-14 12:00:00';
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( string $option ): bool {
        unset( $GLOBALS['_sfb_options'][ $option ] );
        return true;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( string $action ): string {
        return 'test-nonce-' . $action;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'wc_load_cart' ) ) {
    function wc_load_cart(): void {
    }
}

if ( ! class_exists( 'WC' ) ) {
    final class WC {
        /** @var object|null */
        public static $instance;

        public static function instance(): object {
            if ( self::$instance === null ) {
                self::$instance = (object) [ 'cart' => (object) [] ];
            }
            return self::$instance;
        }
    }
}

if ( ! function_exists( 'WC' ) ) {
    function WC(): object {
        return WC::instance();
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public function __construct(
            public string $code = '',
            public string $message = '',
            public mixed $data = null
        ) {
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }
}

if ( ! function_exists( 'wp_parse_url' ) ) {
    function wp_parse_url( string $url ): array|false {
        $parsed = parse_url( $url );
        return is_array( $parsed ) ? $parsed : false;
    }
}

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( string $url ): string {
        return rtrim( $url, '/' ) . '/';
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( string $url ): string {
        return $url;
    }
}

if ( ! function_exists( 'wc_get_checkout_url' ) ) {
    function wc_get_checkout_url(): string {
        return $GLOBALS['_sfb_wc_checkout_url'] ?? 'https://shop.test/checkout/';
    }
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        /** @var array<string, string> */
        private array $headers = [];

        /** @var array<string, mixed> */
        private array $params = [];

        public function __construct(
            private string $method,
            private string $route
        ) {
        }

        public function set_header( string $name, string $value ): void {
            $this->headers[ strtolower( $name ) ] = $value;
        }

        public function get_header( string $name ): string {
            return $this->headers[ strtolower( $name ) ] ?? '';
        }

        public function set_param( string $key, $value ): void {
            $this->params[ $key ] = $value;
        }

        /** @return mixed */
        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }

        public function get_route(): string {
            return $this->route;
        }

        public function get_method(): string {
            return $this->method;
        }
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( string $url ): string {
        return trim( $url );
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        /** @var array<string, mixed> */
        private array $data;

        /** @var array<string, string|string[]> */
        private array $headers = [];

        /** @param array<string, mixed>|null $data */
        public function __construct( $data, private int $status = 200 ) {
            $this->data = is_array( $data ) ? $data : [];
        }

        /** @return array<string, mixed> */
        public function get_data(): array {
            return $this->data;
        }

        public function set_data( $data ): void {
            $this->data = is_array( $data ) ? $data : [];
        }

        public function get_status(): int {
            return $this->status;
        }

        public function header( string $name, $value ): void {
            $this->headers[ $name ] = $value;
        }

        /** @return array<string, string|string[]> */
        public function get_headers(): array {
            return $this->headers;
        }
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $value ): string {
        return trim( $value );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( string $value ): string {
        return trim( $value );
    }
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
    function sanitize_hex_color( string $color ): ?string {
        return preg_match( '/^#(?:[a-f0-9]{3}){1,2}$/i', $color ) ? $color : null;
    }
}

if ( ! function_exists( 'add_settings_error' ) ) {
    function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void {
        unset( $setting, $code, $message, $type );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '' );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        unset( $domain );
        return $text;
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $value ): int {
        return abs( (int) $value );
    }
}

if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( string $title ): string {
        return strtolower( trim( preg_replace( '/[^a-z0-9]+/i', '-', $title ) ?? '', '-' ) );
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( string $hook, ...$args ): void {
        unset( $hook, $args );
    }
}

if ( ! class_exists( 'WC_Product' ) ) {
    class WC_Product {
    }
}

if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( int $post_id ): string {
        return 'https://shop.test/product/' . $post_id;
    }
}

if ( ! function_exists( 'get_the_terms' ) ) {
    function get_the_terms( int $post_id, string $taxonomy ) {
        return false;
    }
}

if ( ! function_exists( 'wc_attribute_label' ) ) {
    function wc_attribute_label( string $name ): string {
        return $name;
    }
}

if ( ! function_exists( 'wc_price' ) ) {
    function wc_price( $price ): string {
        return '$' . $price;
    }
}

if ( ! function_exists( 'get_woocommerce_currency' ) ) {
    function get_woocommerce_currency(): string {
        return 'USD';
    }
}

if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
    function get_woocommerce_currency_symbol(): string {
        return '$';
    }
}

if ( ! function_exists( 'wc_placeholder_img_src' ) ) {
    function wc_placeholder_img_src( string $size = '' ): string {
        return 'https://shop.test/placeholder.jpg';
    }
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
    function wp_get_attachment_image_url( int $attachment_id, string $size = 'full' ) {
        return null;
    }
}

if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
    function wp_get_attachment_metadata( int $attachment_id ) {
        return [];
    }
}

if ( ! function_exists( 'wp_get_attachment_image_srcset' ) ) {
    function wp_get_attachment_image_srcset( int $attachment_id, string $size = 'full' ) {
        return false;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( int $post_id, string $key, bool $single = false ) {
        return $single ? '' : [];
    }
}

if ( ! function_exists( 'strip_tags' ) ) {
    function strip_tags( string $string, $allowed_tags = null ): string {
        return preg_replace( '/<[^>]*>/', '', $string ) ?? $string;
    }
}
