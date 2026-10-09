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
}
