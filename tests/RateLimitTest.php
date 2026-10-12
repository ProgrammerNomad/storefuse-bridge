<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['_sfb_transients'] = [];
        if ( ! function_exists( 'wp_unslash' ) ) {
            function wp_unslash( $value ) {
                return $value;
            }
        }
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-errors.php';
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-http-request.php';
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-auth.php';
    }

    public function test_search_throttle_returns_429_envelope(): void {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';

        $request = new WP_REST_Request( 'GET', '/storefuse/v1/search' );

        for ( $i = 0; $i < StoreFuse_Bridge_Auth::max_attempts_for( 'search' ); $i++ ) {
            $allowed = StoreFuse_Bridge_Auth::throttle_by_ip( $request, 'search' );
            $this->assertNull( $allowed );
        }

        $blocked = StoreFuse_Bridge_Auth::throttle_by_ip( $request, 'search' );
        $this->assertInstanceOf( WP_REST_Response::class, $blocked );
        $data = $blocked->get_data();
        $this->assertSame( 'rate_limit_exceeded', $data['error']['code'] );
        $this->assertSame( 429, $blocked->get_status() );
    }
}
