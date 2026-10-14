<?php
defined( 'ABSPATH' ) || exit;

/**
 * Mobile app UI contract (storefuse.app-ui.v1) - defaults, sanitization, API shape.
 */
class StoreFuse_Bridge_App_Ui {

    public const SCHEMA = 'storefuse.app-ui.v1';

    /** @var list<string> */
    public const HOME_SECTION_IDS = [
        'announcement_bar',
        'hero',
        'featured_categories',
        'trust_items',
        'best_sellers',
        'new_arrivals',
        'promo_banner',
    ];

    /** @var list<string> */
    public const HOMEPAGE_SECTION_IDS = self::HOME_SECTION_IDS;

    /**
     * @return array<string, mixed>
     */
    public static function settings_defaults(): array {
        return [
            'app_ui_catalog_layout'              => 'grid',
            'app_ui_catalog_columns'             => 2,
            'app_ui_catalog_show_stock_badge'    => true,
            'app_ui_shop_title'                  => 'Products',
            'app_ui_search_layout'               => '',
            'app_ui_product_show_sku'            => true,
            'app_ui_product_sticky_add_to_cart'  => true,
            'app_ui_product_related_count'       => 4,
            'app_ui_cart_show_coupon'            => true,
            'app_ui_cart_show_free_shipping_bar' => true,
            'app_ui_cart_empty_cta_href'         => '/shop',
            'app_ui_home_section_order'          => self::HOME_SECTION_IDS,
            'app_ui_home_hide_sections'          => [],
        ];
    }

    /**
     * @param array<string, mixed>|null $settings
     * @return array<string, mixed>
     */
    public static function build( ?array $settings = null ): array {
        $s = $settings ?? StoreFuse_Bridge_Settings::all();

        $catalog = [
            'layout'           => self::sanitize_layout( (string) self::get( $s, 'app_ui_catalog_layout', 'grid' ), 'grid' ),
            'columns'          => self::sanitize_columns( self::get( $s, 'app_ui_catalog_columns', 2 ) ),
            'show_stock_badge' => ! empty( self::get( $s, 'app_ui_catalog_show_stock_badge', true ) ),
        ];

        $search_layout = (string) self::get( $s, 'app_ui_search_layout', '' );
        $search        = [];
        if ( $search_layout !== '' ) {
            $search['layout'] = self::sanitize_layout( $search_layout, $catalog['layout'] );
        }

        $shop_title = sanitize_text_field( (string) self::get( $s, 'app_ui_shop_title', 'Products' ) );
        if ( $shop_title === '' ) {
            $shop_title = 'Products';
        }

        return [
            'schema'  => self::SCHEMA,
            'catalog' => $catalog,
            'screens' => [
                'shop' => [
                    'title' => $shop_title,
                ],
                'search'  => $search,
                'product' => [
                    'show_sku'           => ! empty( self::get( $s, 'app_ui_product_show_sku', true ) ),
                    'sticky_add_to_cart' => ! empty( self::get( $s, 'app_ui_product_sticky_add_to_cart', true ) ),
                    'related_count'      => self::sanitize_related_count( self::get( $s, 'app_ui_product_related_count', 4 ) ),
                ],
                'cart' => [
                    'show_coupon'            => ! empty( self::get( $s, 'app_ui_cart_show_coupon', true ) ),
                    'show_free_shipping_bar' => ! empty( self::get( $s, 'app_ui_cart_show_free_shipping_bar', true ) ),
                    'empty_cta_href'         => self::sanitize_href( (string) self::get( $s, 'app_ui_cart_empty_cta_href', '/shop' ), '/shop' ),
                ],
                'home' => self::home_screen_config( $s ),
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $settings
     * @return array{section_order: list<string>, hide_sections: list<string>}
     */
    public static function home_screen_config( ?array $settings = null ): array {
        $s = $settings ?? StoreFuse_Bridge_Settings::all();

        return [
            'section_order' => self::parse_home_section_order(
                self::get( $s, 'app_ui_home_section_order', self::HOME_SECTION_IDS )
            ),
            'hide_sections' => self::parse_home_hide_sections(
                self::get( $s, 'app_ui_home_hide_sections', [] )
            ),
        ];
    }

    /**
     * Homepage API `layout` block (mirrors screens.home).
     *
     * @return array{section_order: list<string>, hide_sections: list<string>}
     */
    public static function homepage_layout_config(): array {
        return self::home_screen_config();
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function sanitize_settings_patch( array $input ): array {
        $input = self::normalize_admin_input_keys( $input );
        $patch = [];

        if ( ! empty( $input['_sfb_save_app_ui'] ) ) {
            foreach (
                [
                    'app_ui_catalog_show_stock_badge',
                    'app_ui_product_show_sku',
                    'app_ui_product_sticky_add_to_cart',
                    'app_ui_cart_show_coupon',
                    'app_ui_cart_show_free_shipping_bar',
                ] as $bool_key
            ) {
                $patch[ $bool_key ] = ! empty( $input[ $bool_key ] );
            }
        }

        if ( array_key_exists( 'app_ui_catalog_layout', $input ) ) {
            $patch['app_ui_catalog_layout'] = self::sanitize_layout( (string) $input['app_ui_catalog_layout'], 'grid' );
        }
        if ( array_key_exists( 'app_ui_catalog_columns', $input ) ) {
            $patch['app_ui_catalog_columns'] = self::sanitize_columns( $input['app_ui_catalog_columns'] );
        }
        if ( array_key_exists( 'app_ui_catalog_show_stock_badge', $input ) ) {
            $patch['app_ui_catalog_show_stock_badge'] = ! empty( $input['app_ui_catalog_show_stock_badge'] );
        }
        if ( array_key_exists( 'app_ui_shop_title', $input ) ) {
            $patch['app_ui_shop_title'] = sanitize_text_field( (string) $input['app_ui_shop_title'] );
        }
        if ( array_key_exists( 'app_ui_search_layout', $input ) ) {
            $raw = sanitize_text_field( (string) $input['app_ui_search_layout'] );
            $patch['app_ui_search_layout'] = in_array( $raw, [ 'grid', 'list' ], true ) ? $raw : '';
        }
        if ( array_key_exists( 'app_ui_product_show_sku', $input ) ) {
            $patch['app_ui_product_show_sku'] = ! empty( $input['app_ui_product_show_sku'] );
        }
        if ( array_key_exists( 'app_ui_product_sticky_add_to_cart', $input ) ) {
            $patch['app_ui_product_sticky_add_to_cart'] = ! empty( $input['app_ui_product_sticky_add_to_cart'] );
        }
        if ( array_key_exists( 'app_ui_product_related_count', $input ) ) {
            $patch['app_ui_product_related_count'] = self::sanitize_related_count( $input['app_ui_product_related_count'] );
        }
        if ( array_key_exists( 'app_ui_cart_show_coupon', $input ) ) {
            $patch['app_ui_cart_show_coupon'] = ! empty( $input['app_ui_cart_show_coupon'] );
        }
        if ( array_key_exists( 'app_ui_cart_show_free_shipping_bar', $input ) ) {
            $patch['app_ui_cart_show_free_shipping_bar'] = ! empty( $input['app_ui_cart_show_free_shipping_bar'] );
        }
        if ( array_key_exists( 'app_ui_cart_empty_cta_href', $input ) ) {
            $patch['app_ui_cart_empty_cta_href'] = self::sanitize_href( (string) $input['app_ui_cart_empty_cta_href'], '/shop' );
        }
        if ( array_key_exists( 'app_ui_home_section_order', $input ) ) {
            $patch['app_ui_home_section_order'] = self::parse_home_section_order( $input['app_ui_home_section_order'] );
        }
        if ( array_key_exists( 'app_ui_home_hide_sections', $input ) ) {
            $patch['app_ui_home_hide_sections'] = self::parse_home_hide_sections( $input['app_ui_home_hide_sections'] );
        }

        return $patch;
    }

    /**
     * @param array<string, mixed> $sections
     * @return array<string, mixed>
     */
    public static function apply_homepage_layout( array $sections ): array {
        $home  = self::home_screen_config();
        $hide  = array_flip( $home['hide_sections'] );
        $order = $home['section_order'];

        foreach ( array_keys( $hide ) as $key ) {
            unset( $sections[ $key ] );
        }

        $ordered = [];
        foreach ( $order as $key ) {
            if ( isset( $sections[ $key ] ) ) {
                $ordered[ $key ] = $sections[ $key ];
                unset( $sections[ $key ] );
            }
        }
        foreach ( $sections as $key => $value ) {
            $ordered[ $key ] = $value;
        }

        $ordered['layout'] = $home;

        return $ordered;
    }

    public static function sanitize_layout( string $raw, string $fallback = 'grid' ): string {
        return in_array( $raw, [ 'grid', 'list' ], true ) ? $raw : $fallback;
    }

    /**
     * @return list<string>
     */
    public static function parse_section_order( mixed $raw ): array {
        return self::parse_home_section_order( $raw );
    }

    /**
     * @return list<string>
     */
    public static function parse_hidden_sections( mixed $raw ): array {
        return self::parse_home_hide_sections( $raw );
    }

    /**
     * @return list<string>
     */
    public static function parse_home_section_order( mixed $raw ): array {
        $ids = self::HOME_SECTION_IDS;
        if ( is_string( $raw ) && $raw !== '' ) {
            $raw = array_map( 'trim', explode( ',', $raw ) );
        }
        if ( ! is_array( $raw ) ) {
            return $ids;
        }

        $valid = array_flip( $ids );
        $out   = [];
        foreach ( $raw as $item ) {
            $key = sanitize_key( (string) $item );
            if ( $key !== '' && isset( $valid[ $key ] ) && ! in_array( $key, $out, true ) ) {
                $out[] = $key;
            }
        }
        foreach ( $ids as $id ) {
            if ( ! in_array( $id, $out, true ) ) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /**
     * @return list<string>
     */
    public static function parse_home_hide_sections( mixed $raw ): array {
        if ( is_string( $raw ) && $raw !== '' ) {
            $raw = array_map( 'trim', explode( ',', $raw ) );
        }
        if ( ! is_array( $raw ) ) {
            return [];
        }
        $valid = array_flip( self::HOME_SECTION_IDS );
        $out   = [];
        foreach ( $raw as $item ) {
            $key = sanitize_key( (string) $item );
            if ( $key !== '' && isset( $valid[ $key ] ) && ! in_array( $key, $out, true ) ) {
                $out[] = $key;
            }
        }
        return $out;
    }

    private static function sanitize_columns( mixed $raw ): int {
        $columns = (int) $raw;
        return $columns === 3 ? 3 : 2;
    }

    private static function sanitize_related_count( mixed $raw ): int {
        return min( 12, max( 0, (int) $raw ) );
    }

    private static function sanitize_href( string $raw, string $fallback ): string {
        $raw = trim( $raw );
        if ( $raw === '' ) {
            return $fallback;
        }
        if ( str_starts_with( $raw, '/' ) ) {
            return '/' . ltrim( sanitize_text_field( $raw ), '/' );
        }
        $url = esc_url_raw( $raw, [ 'https', 'http' ] );
        return $url !== '' ? $url : $fallback;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function get( array $settings, string $key, mixed $default ): mixed {
        if ( array_key_exists( $key, $settings ) ) {
            return $settings[ $key ];
        }
        $legacy = self::legacy_key_map()[ $key ] ?? null;
        if ( $legacy !== null && array_key_exists( $legacy, $settings ) ) {
            return $settings[ $legacy ];
        }
        return $default;
    }

    /**
     * Map canonical app_ui_* keys to legacy admin form keys.
     *
     * @return array<string, string>
     */
    private static function legacy_key_map(): array {
        return [
            'app_ui_catalog_layout'              => 'ui_catalog_layout',
            'app_ui_catalog_columns'             => 'ui_catalog_columns',
            'app_ui_catalog_show_stock_badge'    => 'ui_show_stock_badge',
            'app_ui_shop_title'                  => 'ui_shop_title',
            'app_ui_search_layout'               => 'ui_search_layout',
            'app_ui_product_show_sku'            => 'ui_pdp_show_sku',
            'app_ui_product_sticky_add_to_cart'  => 'ui_pdp_sticky_add_to_cart',
            'app_ui_product_related_count'       => 'ui_pdp_related_count',
            'app_ui_cart_show_coupon'            => 'ui_cart_show_coupon',
            'app_ui_cart_show_free_shipping_bar' => 'ui_cart_show_free_shipping_bar',
            'app_ui_cart_empty_cta_href'         => 'ui_cart_empty_cta_href',
            'app_ui_home_section_order'          => 'homepage_section_order',
            'app_ui_home_hide_sections'          => 'homepage_section_hidden',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function normalize_admin_input_keys( array $input ): array {
        foreach ( self::legacy_key_map() as $canonical => $legacy ) {
            if ( array_key_exists( $legacy, $input ) && ! array_key_exists( $canonical, $input ) ) {
                $input[ $canonical ] = $input[ $legacy ];
            }
        }
        return $input;
    }
}
