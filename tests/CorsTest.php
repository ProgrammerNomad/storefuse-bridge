<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CorsTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-settings.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-cors.php';
        StoreFuse_Bridge_Settings::reset_cache();
    }

    public function test_allowed_origins_parses_lines(): void
    {
        StoreFuse_Bridge_Settings::set([
            'cors_allowed_origins' => "https://a.test\r\nhttps://b.test\n\n",
        ]);

        $this->assertSame(
            [ 'https://a.test', 'https://b.test' ],
            StoreFuse_Bridge_Cors::allowed_origins()
        );
    }

    public function test_suppress_core_cors_for_storefuse_routes(): void
    {
        $request = new WP_REST_Request('GET', '/storefuse/v1/status');
        $this->assertFalse(
            StoreFuse_Bridge_Cors::suppress_core_cors_for_storefuse(true, $request)
        );

        $other = new WP_REST_Request('GET', '/wp/v2/posts');
        $this->assertTrue(
            StoreFuse_Bridge_Cors::suppress_core_cors_for_storefuse(true, $other)
        );
    }
}
