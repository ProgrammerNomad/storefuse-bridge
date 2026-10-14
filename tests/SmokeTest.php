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
        $this->assertStringContainsString("STOREFUSE_BRIDGE_VERSION',  '1.1.0'", $contents);
        $this->assertStringContainsString('build-info.php', $contents);
    }

    public function test_deploy_script_declares_critical_rest_schema_path(): void
    {
        $script = dirname(__DIR__) . '/scripts/deploy-local.ps1';
        $this->assertFileExists($script);
        $contents = file_get_contents($script);
        $this->assertIsString($contents);
        $this->assertStringContainsString('class-rest-schema.php', $contents);
    }
}
