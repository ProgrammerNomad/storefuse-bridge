<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ErrorsTest extends TestCase
{
    public function test_cart_quantity_errors_use_storefuse_envelope(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-errors.php';

        $response = StoreFuse_Bridge_Errors::sold_individually();
        $data     = $response->get_data();

        $this->assertSame('storefuse.error.v1', $data['schema']);
        $this->assertSame('sold_individually', $data['error']['code']);
        $this->assertSame(400, $data['error']['status']);
        $this->assertSame('1.0.0', $data['api_version']);
    }

    public function test_forbidden_maps_to_idor_contract(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-errors.php';

        $response = StoreFuse_Bridge_Errors::forbidden();
        $data     = $response->get_data();

        $this->assertSame('forbidden', $data['error']['code']);
        $this->assertSame(403, $response->get_status());
    }
}
