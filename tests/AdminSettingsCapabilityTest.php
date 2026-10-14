<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminSettingsCapabilityTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-admin.php';
    }

    public function test_default_settings_capability_is_manage_woocommerce(): void
    {
        $this->assertSame('manage_woocommerce', StoreFuse_Bridge_Admin::settings_capability());
    }

    public function test_option_page_capability_filter_uses_settings_capability(): void
    {
        add_filter(
            'option_page_capability_storefuse_bridge_settings_group',
            static fn() => StoreFuse_Bridge_Admin::settings_capability()
        );

        $this->assertSame(
            'manage_woocommerce',
            apply_filters('option_page_capability_storefuse_bridge_settings_group', 'manage_options')
        );
    }
}
