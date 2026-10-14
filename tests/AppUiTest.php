<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AppUiTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/tests/wp-stubs.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-app-ui.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-settings.php';
        StoreFuse_Bridge_Settings::reset_cache();

        if (! function_exists('absint')) {
            function absint($n)
            {
                return abs((int) $n);
            }
        }
        if (! function_exists('sanitize_text_field')) {
            function sanitize_text_field($str)
            {
                return is_string($str) ? trim($str) : '';
            }
        }
        if (! function_exists('sanitize_key')) {
            function sanitize_key($key)
            {
                return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
            }
        }
        if (! function_exists('esc_url_raw')) {
            function esc_url_raw($url)
            {
                return (string) $url;
            }
        }
    }

    public function test_sanitize_layout_rejects_unknown(): void
    {
        $this->assertSame('grid', StoreFuse_Bridge_App_Ui::sanitize_layout('masonry'));
    }

    public function test_parse_section_order_keeps_known_ids(): void
    {
        $order = StoreFuse_Bridge_App_Ui::parse_section_order('hero,promo_banner,invalid');
        $this->assertSame('hero', $order[0]);
        $this->assertContains('promo_banner', $order);
        $this->assertNotContains('invalid', $order);
    }

    public function test_build_defaults_when_settings_unavailable(): void
    {
        $ui = StoreFuse_Bridge_App_Ui::build();
        $this->assertSame('storefuse.app-ui.v1', $ui['schema']);
        $this->assertSame('grid', $ui['catalog']['layout']);
        $this->assertSame(2, $ui['catalog']['columns']);
    }
}
