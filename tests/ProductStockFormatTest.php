<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Minimal WC product double for stock formatting tests. */
class Sfb_Test_WC_Product_Stock extends WC_Product {
    public function get_stock_status(): string {
        return 'instock';
    }

    public function is_in_stock(): bool {
        return false;
    }

    public function is_purchasable(): bool {
        return false;
    }

    public function get_type(): string {
        return 'simple';
    }

    public function get_id(): int {
        return 1;
    }

    public function get_slug(): string {
        return 'test';
    }

    public function get_name(): string {
        return 'Test';
    }

    public function get_description(): string {
        return '';
    }

    public function get_short_description(): string {
        return '';
    }

    public function get_price(): string {
        return '10';
    }

    public function get_regular_price(): string {
        return '10';
    }

    public function get_sale_price(): string {
        return '';
    }

    public function is_on_sale(): bool {
        return false;
    }

    public function get_stock_quantity() {
        return null;
    }

    public function get_sku(): string {
        return '';
    }

    public function get_gallery_image_ids(): array {
        return [];
    }

    public function get_image_id() {
        return 0;
    }

    public function get_average_rating(): string {
        return '0';
    }

    public function get_rating_count(): int {
        return 0;
    }

    public function get_attributes(): array {
        return [];
    }

    public function is_type( string $type ): bool {
        return $type === 'simple';
    }

    public function get_date_on_sale_to() {
        return null;
    }
}

/** Variable parent reports in stock but has no purchasable variations. */
final class Sfb_Test_WC_Product_Variable_Empty extends Sfb_Test_WC_Product_Stock {
    public function is_in_stock(): bool {
        return true;
    }

    public function get_type(): string {
        return 'variable';
    }

    public function is_type( string $type ): bool {
        return $type === 'variable';
    }

    public function get_available_variations(): array {
        return [];
    }
}

final class ProductStockFormatTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/tests/wp-stubs.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-format.php';
    }

    public function test_product_uses_is_in_stock_for_stock_status(): void
    {
        $formatted = StoreFuse_Bridge_Format::product( new Sfb_Test_WC_Product_Stock() );
        $this->assertSame( 'outofstock', $formatted['stock_status'] );
        $this->assertFalse( $formatted['is_in_stock'] );
    }

    public function test_variable_product_with_no_available_variations_is_out_of_stock(): void
    {
        $formatted = StoreFuse_Bridge_Format::product( new Sfb_Test_WC_Product_Variable_Empty() );
        $this->assertSame( 'outofstock', $formatted['stock_status'] );
        $this->assertFalse( $formatted['is_in_stock'] );
        $this->assertSame( [], $formatted['variations'] );
    }

    public function test_product_stock_normalizes_simple_product_status(): void
    {
        $stock = StoreFuse_Bridge_Format::product_stock( new Sfb_Test_WC_Product_Stock() );
        $this->assertSame( 'outofstock', $stock['stock_status'] );
        $this->assertFalse( $stock['is_in_stock'] );
    }

    public function test_product_stock_marks_variable_without_variations_out_of_stock(): void
    {
        $stock = StoreFuse_Bridge_Format::product_stock( new Sfb_Test_WC_Product_Variable_Empty() );
        $this->assertSame( 'outofstock', $stock['stock_status'] );
        $this->assertFalse( $stock['is_in_stock'] );
    }
}
