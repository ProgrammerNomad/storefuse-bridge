<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RestSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-rest-schema.php';
    }

    public function test_mirror_list_items_duplicates_resource_array(): void
    {
        $payload = StoreFuse_Bridge_Rest_Schema::mirror_list_items(
            [
                'orders' => [ [ 'id' => '1' ] ],
                'meta'   => [ 'total' => 1 ],
            ],
            'orders'
        );

        $this->assertSame($payload['orders'], $payload['items']);
    }

    public function test_cart_item_key_prefers_cart_item_key_param(): void
    {
        $request = new WP_REST_Request('PUT', '/storefuse/v1/cart/update');
        $request->set_param('cart_item_key', 'abc123');
        $request->set_param('key', 'legacy');

        $this->assertSame('abc123', StoreFuse_Bridge_Rest_Schema::cart_item_key_from_request($request));
    }

    public function test_cart_item_key_falls_back_to_key_alias(): void
    {
        $request = new WP_REST_Request('DELETE', '/storefuse/v1/cart/remove');
        $request->set_param('key', 'line-key-9');

        $this->assertSame('line-key-9', StoreFuse_Bridge_Rest_Schema::cart_item_key_from_request($request));
    }
}
