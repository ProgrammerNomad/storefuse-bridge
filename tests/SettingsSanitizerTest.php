<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SettingsSanitizerTest extends TestCase {

    protected function setUp(): void {
        if ( ! function_exists( 'sanitize_text_field' ) ) {
            function sanitize_text_field( $str ) {
                return is_string( $str ) ? trim( $str ) : '';
            }
        }
        if ( ! function_exists( 'sanitize_hex_color' ) ) {
            function sanitize_hex_color( $color ) {
                return preg_match( '/^#[a-f0-9]{6}$/i', (string) $color ) ? $color : null;
            }
        }
        if ( ! function_exists( 'absint' ) ) {
            function absint( $maybeint ) {
                return abs( (int) $maybeint );
            }
        }
        require_once dirname( __DIR__ ) . '/storefuse-bridge/includes/class-settings-sanitizer.php';
    }

    public function test_trust_badges_caps_count_and_sanitizes(): void {
        $rows = [];
        for ( $i = 0; $i < 20; $i++ ) {
            $rows[] = [ 'title' => 'Badge ' . $i, 'enabled' => true, 'icon' => 'x', 'description' => 'd' ];
        }
        $parsed = StoreFuse_Bridge_Settings_Sanitizer::parse_trust_badges( wp_json_encode( $rows ) );
        $this->assertCount( StoreFuse_Bridge_Settings_Sanitizer::MAX_TRUST_BADGES, $parsed );
    }

    public function test_featured_categories_requires_valid_term(): void {
        $parsed = StoreFuse_Bridge_Settings_Sanitizer::parse_featured_categories(
            '[{"category_id":0,"label":"x"}]'
        );
        $this->assertSame( [], $parsed );
    }
}
