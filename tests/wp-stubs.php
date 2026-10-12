<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/storefuse-bridge/' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_VERSION' ) ) {
    define( 'STOREFUSE_BRIDGE_VERSION', '1.0.2' );
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

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '' );
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( string $hook, ...$args ): void {
        unset( $hook, $args );
    }
}
