<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_plugin_version_constant_file_exists(): void
    {
        $main = dirname(__DIR__) . '/storefuse-bridge/storefuse-bridge.php';
        $this->assertFileExists($main);
        $contents = file_get_contents($main);
        $this->assertIsString($contents);
        $this->assertStringContainsString("STOREFUSE_BRIDGE_VERSION',  '1.0.0'", $contents);
    }
}
