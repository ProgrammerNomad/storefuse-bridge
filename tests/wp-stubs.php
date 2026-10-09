<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/storefuse-bridge/' );
}

if ( ! defined( 'STOREFUSE_BRIDGE_VERSION' ) ) {
    define( 'STOREFUSE_BRIDGE_VERSION', '1.0.1' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
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

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        /** @param array<string, mixed> $data */
        public function __construct(
            private array $data,
            private int $status = 200
        ) {
        }

        /** @return array<string, mixed> */
        public function get_data(): array {
            return $this->data;
        }

        public function get_status(): int {
            return $this->status;
        }
    }
}
