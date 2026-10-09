<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlValidatorTest extends TestCase
{
    public function test_rejects_http_scheme(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-url-validator.php';

        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('http://example.com/hook');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_url', $result->get_error_code());
    }

    public function test_rejects_localhost(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-url-validator.php';

        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://localhost/revalidate');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_accepts_public_https_host(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-url-validator.php';

        $result = StoreFuse_Bridge_Url_Validator::validate_webhook_base_url('https://example.com/api/revalidate');
        $this->assertTrue($result);
    }
}
