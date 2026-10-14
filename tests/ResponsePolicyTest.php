<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResponsePolicyTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-response.php';
    }

    public function test_public_response_gets_etag_and_honors_if_none_match(): void
    {
        $body = [
            'schema'      => 'storefuse.products.v1',
            'api_version' => '1.0.2',
            'data'        => [ 'items' => [], 'meta' => [ 'total' => 0 ] ],
        ];
        $response = StoreFuse_Bridge_Response::success($body, 'storefuse.products.v1');
        $response = StoreFuse_Bridge_Response::with_public_cache($response, 300);

        $request = new WP_REST_Request('GET', '/storefuse/v1/products');
        $with_tag = StoreFuse_Bridge_Response::apply_conditional_etag($response, $request);
        $etag     = $with_tag->get_headers()['ETag'] ?? '';
        $this->assertNotSame('', $etag);

        $request->set_header('If-None-Match', is_array($etag) ? $etag[0] : $etag);
        $not_modified = StoreFuse_Bridge_Response::apply_conditional_etag($response, $request);
        $this->assertSame(304, $not_modified->get_status());
    }

    public function test_version_policy_header(): void
    {
        $response = StoreFuse_Bridge_Response::success([ 'ok' => true ], 'storefuse.status.v1');
        $response = StoreFuse_Bridge_Response::with_version_policy($response);
        $this->assertSame('v1', $response->get_headers()['X-StoreFuse-API-Version'] ?? null);
    }
}
