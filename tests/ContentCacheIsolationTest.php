<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ContentCacheIsolationTest extends TestCase
{
    public function testFlushInvalidatesContentWithoutErasingSecurityState(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-cache.php';
        $GLOBALS['_sfb_options'] = [];
        $GLOBALS['_sfb_transients'] = [];
        set_transient('sfb_throttle_login_customer', 9, 900);
        set_transient('sfb_cart_tok_customer', 'cart-secret', 900);
        set_transient('sfb_idem_attempt', ['order_id' => 123], 900);
        StoreFuse_Bridge_Cache::set('products_query', ['old']);
        $this->assertSame(['old'], StoreFuse_Bridge_Cache::get('products_query'));
        StoreFuse_Bridge_Cache::flush_all();
        $this->assertNull(StoreFuse_Bridge_Cache::get('products_query'));
        $this->assertSame(9, get_transient('sfb_throttle_login_customer'));
        $this->assertSame('cart-secret', get_transient('sfb_cart_tok_customer'));
        $this->assertSame(['order_id' => 123], get_transient('sfb_idem_attempt'));
        StoreFuse_Bridge_Cache::set('products_query', ['new']);
        $this->assertSame(['new'], StoreFuse_Bridge_Cache::get('products_query'));
    }
}
