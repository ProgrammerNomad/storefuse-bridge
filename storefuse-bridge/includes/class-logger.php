<?php
defined( 'ABSPATH' ) || exit;

/**
 * Structured logging via WooCommerce logger (no PII, tokens, or passwords).
 */
class StoreFuse_Bridge_Logger {

    private const SOURCE = 'storefuse-bridge';

    public static function error( string $message, array $context = [] ): void {
        self::log( 'error', $message, $context );
    }

    public static function warning( string $message, array $context = [] ): void {
        self::log( 'warning', $message, $context );
    }

    public static function info( string $message, array $context = [] ): void {
        self::log( 'info', $message, $context );
    }

    private static function log( string $level, string $message, array $context ): void {
        if ( ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        $safe = [];
        foreach ( $context as $key => $value ) {
            if ( is_scalar( $value ) || $value === null ) {
                $safe[ $key ] = $value;
            }
        }

        wc_get_logger()->log( $level, $message, array_merge( [ 'source' => self::SOURCE ], $safe ) );
    }
}
