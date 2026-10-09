<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CartSessionTokenTest extends TestCase {

    protected function setUp(): void {
        if ( ! function_exists( 'wp_salt' ) ) {
            function wp_salt( $scheme = 'auth' ) {
                return 'test-salt-' . $scheme;
            }
        }
        if ( ! function_exists( 'apply_filters' ) ) {
            function apply_filters( $tag, $value ) {
                return $value;
            }
        }
        if ( ! function_exists( 'sanitize_text_field' ) ) {
            function sanitize_text_field( $str ) {
                return is_string( $str ) ? trim( $str ) : '';
            }
        }
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-cart-session-token.php';
    }

    public function test_validate_rejects_garbage_token(): void {
        $this->assertSame( '', StoreFuse_Bridge_Cart_Session_Token::validate_and_resolve( 'not-a-token' ) );
    }
}
