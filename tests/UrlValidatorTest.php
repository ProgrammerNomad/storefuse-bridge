<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-url-validator.php';
    }

    public function test_rejects_http_scheme(): void
    {
        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('http://example.com/hook');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_url', $result->get_error_code());
    }

    public function test_rejects_localhost(): void
    {
        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://localhost/revalidate');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_accepts_public_https_host(): void
    {
        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://example.com/api/revalidate');
        $this->assertTrue($result);
    }

    public function test_rejects_empty_dns_resolution(): void
    {
        add_filter(
            'storefuse_bridge_resolve_host_ips',
            static function ( $ips, string $host ) {
                if ( $host === 'unresolvable.example' ) {
                    return [];
                }
                return $ips;
            },
            10,
            2
        );

        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://unresolvable.example/hook');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_url', $result->get_error_code());
        $this->assertStringContainsString('resolved', strtolower($result->get_error_message()));
    }

    public function test_rejects_private_ip_from_resolver(): void
    {
        add_filter(
            'storefuse_bridge_resolve_host_ips',
            static function ( $ips, string $host ) {
                if ( $host === 'internal.example' ) {
                    return [ '10.0.0.1' ];
                }
                return $ips;
            },
            10,
            2
        );

        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://internal.example/hook');
        $this->assertInstanceOf(WP_Error::class, $result);
    }
}
