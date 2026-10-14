<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HealthSnapshotTest extends TestCase
{
    public function test_health_snapshot_includes_requirements_and_store(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-wc-compat.php';

        $health = StoreFuse_Bridge_WC_Compat::health_snapshot();

        $this->assertArrayHasKey('ok', $health);
        $this->assertArrayHasKey('requirements', $health);
        $this->assertArrayHasKey('store', $health);
        $this->assertArrayHasKey('woocommerce', $health['requirements']);
        $this->assertArrayHasKey('multilingual', $health['store']);
    }
}
