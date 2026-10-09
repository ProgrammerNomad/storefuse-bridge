<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared REST argument validation helpers.
 */
class StoreFuse_Bridge_Validator {

    /**
     * @return true|WP_Error
     */
    public static function required_email( mixed $value ): bool|WP_Error {
        $email = is_string( $value ) ? sanitize_email( $value ) : '';
        if ( $email === '' || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'A valid email address is required.' );
        }
        return true;
    }

    /**
     * @return true|WP_Error
     */
    public static function required_string( mixed $value, int $min_length = 1 ): bool|WP_Error {
        if ( ! is_string( $value ) ) {
            return new WP_Error( 'validation_error', 'This field must be a string.' );
        }
        $trimmed = trim( $value );
        if ( strlen( $trimmed ) < $min_length ) {
            return new WP_Error( 'validation_error', 'This field is required.' );
        }
        return true;
    }

    public static function optional_int( mixed $value, int $default, int $min, int $max ): int {
        if ( $value === null || $value === '' ) {
            return $default;
        }
        $int = (int) $value;
        return max( $min, min( $max, $int ) );
    }

    public static function max_per_page( mixed $value, int $default = 20, int $max = 100 ): int {
        return self::optional_int( $value, $default, 1, $max );
    }
}
