<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CheckoutIdempotencyTest extends TestCase {

    protected function setUp(): void {
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-errors.php';
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-checkout-idempotency.php';
    }

    public function test_validate_key_rejects_short_keys(): void {
        $result = StoreFuse_Bridge_Checkout_Idempotency::validate_key( 'abc' );
        $this->assertInstanceOf( WP_REST_Response::class, $result );
    }

    public function test_storage_key_differs_by_scope(): void {
        $a = StoreFuse_Bridge_Checkout_Idempotency::storage_key( 'session:1', 'test-key-12345678' );
        $b = StoreFuse_Bridge_Checkout_Idempotency::storage_key( 'session:2', 'test-key-12345678' );
        $this->assertNotSame( $a, $b );
    }

    public function test_empty_key_is_allowed(): void {
        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::validate_key( '' ) );
    }
}
