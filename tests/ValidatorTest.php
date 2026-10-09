<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_email_rejects_invalid(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-validator.php';

        if (! function_exists('sanitize_email')) {
            function sanitize_email($email)
            {
                return filter_var((string) $email, FILTER_SANITIZE_EMAIL);
            }
        }
        if (! function_exists('is_email')) {
            function is_email($email)
            {
                return (bool) filter_var((string) $email, FILTER_VALIDATE_EMAIL);
            }
        }

        $result = StoreFuse_Bridge_Validator::required_email('not-an-email');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_email', $result->get_error_code());
    }

    public function test_max_per_page_clamps(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-validator.php';

        $this->assertSame(100, StoreFuse_Bridge_Validator::max_per_page(500, 20, 100));
        $this->assertSame(20, StoreFuse_Bridge_Validator::max_per_page(null, 20, 100));
        $this->assertSame(1, StoreFuse_Bridge_Validator::max_per_page(0, 20, 100));
    }
}
