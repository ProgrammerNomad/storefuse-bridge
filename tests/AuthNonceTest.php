<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthNonceTest extends TestCase {

    protected function setUp(): void {
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-auth.php';
    }

    public function test_bootstrap_nonces_includes_cart_nonce(): void {
        $payload = StoreFuse_Bridge_Auth::bootstrap_nonces();

        $this->assertSame( 'test-nonce-wp_rest', $payload['nonce'] );
        $this->assertSame( 'test-nonce-wc_store_api', $payload['cart_nonce'] );
        $this->assertArrayHasKey( 'nonce', $payload );
        $this->assertArrayHasKey( 'cart_nonce', $payload );
    }

    public function test_check_cart_nonce_rejects_missing_header(): void {
        if ( ! function_exists( 'wp_verify_nonce' ) ) {
            function wp_verify_nonce( string $nonce, string $action ): int|false {
                return str_starts_with( $nonce, 'test-nonce-' ) ? 1 : false;
            }
        }

        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-errors.php';

        $request = new WP_REST_Request( 'POST', '/storefuse/v1/cart/add' );

        $response = StoreFuse_Bridge_Auth::check_cart_nonce( $request );
        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $this->assertSame( 'invalid_nonce', $response->get_data()['error']['code'] );
    }
}
