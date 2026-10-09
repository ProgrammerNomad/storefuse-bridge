<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CheckoutIdempotencyTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['_sfb_options']    = [];
        $GLOBALS['_sfb_transients'] = [];
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

    public function test_acquire_lock_allows_only_one_holder(): void {
        $lock = 'sfb_test_lock_' . uniqid( '', true );

        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
        $this->assertFalse( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );

        StoreFuse_Bridge_Checkout_Idempotency::release_lock( $lock );

        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
        StoreFuse_Bridge_Checkout_Idempotency::release_lock( $lock );
    }

    public function test_release_lock_after_validation_failure_path(): void {
        $lock = 'sfb_validation_fail_' . uniqid( '', true );

        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
        StoreFuse_Bridge_Checkout_Idempotency::release_lock( $lock );

        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
        StoreFuse_Bridge_Checkout_Idempotency::release_lock( $lock );
    }

    public function test_filter_can_override_acquire(): void {
        $lock = 'sfb_filter_lock';
        add_filter(
            'storefuse_bridge_idempotency_acquire',
            static function ( $result, string $key ) use ( $lock ) {
                if ( $key === $lock ) {
                    return true;
                }
                return $result;
            },
            10,
            2
        );

        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
        $this->assertTrue( StoreFuse_Bridge_Checkout_Idempotency::acquire_lock( $lock ) );
    }
}
