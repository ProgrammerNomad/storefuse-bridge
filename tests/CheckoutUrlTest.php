<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CheckoutUrlTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_sfb_options'] = [];
        $GLOBALS['_sfb_wc_checkout_url'] = 'https://shop.test/checkout/';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-settings.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-module.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/modules/class-module-checkout.php';
    }

    public function test_checkout_url_uses_configured_page_on_same_host(): void
    {
        StoreFuse_Bridge_Settings::set(
            [
                'checkout_page_url' => 'https://shop.test/custom-checkout/',
            ]
        );

        $this->assertSame(
            'https://shop.test/custom-checkout/',
            StoreFuse_Bridge_Module_Checkout::checkout_url()
        );
    }

    public function test_checkout_url_matches_config_used_by_get_config(): void
    {
        StoreFuse_Bridge_Settings::set(
            [
                'checkout_page_url' => 'https://shop.test/pay/',
            ]
        );

        $configured = StoreFuse_Bridge_Settings::get( 'checkout_page_url', '' );
        $resolved   = StoreFuse_Bridge_Module_Checkout::checkout_url();

        $this->assertSame( $resolved, esc_url_raw( $configured ) );
    }

    public function test_checkout_url_falls_back_when_host_differs(): void
    {
        StoreFuse_Bridge_Settings::set(
            [
                'checkout_page_url' => 'https://other.test/checkout/',
            ]
        );

        $this->assertSame(
            'https://shop.test/checkout/',
            StoreFuse_Bridge_Module_Checkout::checkout_url()
        );
    }
}
