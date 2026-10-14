<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminSettingsPreservationTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-settings-sanitizer.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-app-ui.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-settings.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-module.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/modules/class-module-status.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-cache.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-admin.php';

        $GLOBALS['_sfb_options'] = [];
        $GLOBALS['_sfb_actions'] = [];
        $GLOBALS['_sfb_menus'] = [];
        StoreFuse_Bridge_Settings::reset_cache();
    }

    /**
     * @dataProvider settingsForms
     * @param array<string, mixed> $input
     * @param list<string> $ownedKeys
     * @param array<string, mixed> $expected
     */
    public function test_form_save_updates_its_fields_and_preserves_unrelated_settings(
        array $input,
        array $ownedKeys,
        array $expected
    ): void {
        $seed = StoreFuse_Bridge_Settings::defaults();
        foreach ( array_keys( $seed ) as $index => $key ) {
            $seed[ $key ] = 'sentinel-' . $index;
        }
        $seed['revalidation_secret'] = 'existing-secret';
        $seed['app_ui_product_show_sku'] = true;
        $seed['app_ui_home_hide_sections'] = [ 'hero' ];
        $seed['module_products_enabled'] = true;
        $seed['module_orders_enabled'] = true;
        $seed['unrelated_custom_value'] = [ 'keep' => 'unchanged' ];
        $GLOBALS['_sfb_options']['storefuse_bridge_settings'] = $seed;
        StoreFuse_Bridge_Settings::reset_cache();

        $result = ( new StoreFuse_Bridge_Admin() )->sanitize_settings( $input );

        foreach ( $seed as $key => $value ) {
            if ( ! in_array( $key, $ownedKeys, true ) ) {
                self::assertSame( $value, $result[ $key ] ?? null, 'Unrelated setting changed: ' . $key );
            }
        }
        foreach ( $expected as $key => $value ) {
            self::assertSame( $value, $result[ $key ] ?? null, 'Submitted setting was not applied: ' . $key );
        }
        self::assertArrayNotHasKey( '_sfb_save_app_ui', $result );
        self::assertArrayNotHasKey( '_sfb_save_modules', $result );
        self::assertArrayNotHasKey( '_sfb_clear_revalidation_secret', $result );
    }

    /** @return array<string, array{array<string, mixed>, list<string>, array<string, mixed>}> */
    public static function settingsForms(): array
    {
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-app-ui.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/class-module.php';
        require_once dirname(__DIR__) . '/storefuse-bridge/includes/modules/class-module-status.php';

        $appUiKeys = array_keys( StoreFuse_Bridge_App_Ui::settings_defaults() );
        $moduleKeys = array_map(
            static fn( $id ) => 'module_' . $id . '_enabled',
            StoreFuse_Bridge_Module_Status::toggleable_module_ids()
        );

        return [
            'Setup' => [
                [ 'primary_client' => 'nextjs', 'storefront_url' => 'https://shop.example.com', 'storefront_reset_path' => '/account/reset' ],
                [ 'primary_client', 'storefront_url', 'storefront_reset_path' ],
                [ 'primary_client' => 'nextjs', 'storefront_url' => 'https://shop.example.com', 'storefront_reset_path' => '/account/reset' ],
            ],
            'Branding' => [
                [ 'app_primary_color' => '#112233', 'app_support_url' => 'https://shop.example.com/help', 'announcement_bar_enabled' => '0', 'announcement_bar_text' => 'Notice' ],
                [ 'app_primary_color', 'app_support_url', 'announcement_bar_enabled', 'announcement_bar_text', 'announcement_bar_bg_color', 'announcement_bar_link' ],
                [ 'app_primary_color' => '#112233', 'app_support_url' => 'https://shop.example.com/help', 'announcement_bar_enabled' => false, 'announcement_bar_text' => 'Notice' ],
            ],
            'Homepage' => [
                [ 'hero_headline' => 'New headline', 'homepage_promo_banner_enabled' => '0', 'featured_categories' => '[{"term_id":9,"label":"New"}]' ],
                [ 'hero_badge_text', 'hero_headline', 'hero_headline_highlight', 'hero_subheadline', 'hero_cta_primary_label', 'hero_cta_primary_href', 'hero_cta_secondary_label', 'hero_cta_secondary_href', 'hero_image_id', 'hero_rating_text', 'hero_shipping_text', 'featured_categories', 'homepage_best_sellers_heading', 'homepage_best_sellers_source', 'homepage_best_sellers_count', 'homepage_best_sellers_ids', 'homepage_new_arrivals_heading', 'homepage_new_arrivals_count', 'homepage_new_arrivals_category', 'homepage_promo_banner_enabled', 'homepage_promo_banner_headline', 'homepage_promo_banner_body', 'homepage_promo_banner_cta_label', 'homepage_promo_banner_cta_href', 'homepage_promo_banner_bg_color' ],
                [ 'hero_headline' => 'New headline', 'homepage_promo_banner_enabled' => false, 'featured_categories' => '[{"category_id":9,"term_id":9,"label":"New","icon":"","color":""}]' ],
            ],
            'Social & Trust' => [
                [ 'social_instagram' => 'https://instagram.com/store', 'trust_badges' => '[{"title":"Secure","enabled":true}]' ],
                [ 'social_instagram', 'social_facebook', 'social_twitter', 'social_youtube', 'social_pinterest', 'social_whatsapp', 'trust_badges' ],
                [ 'social_instagram' => 'https://instagram.com/store', 'trust_badges' => '[{"icon":"","title":"Secure","description":"","enabled":true}]' ],
            ],
            'App Layout' => [
                [ '_sfb_save_app_ui' => '1', 'app_ui_theme_mode' => 'light', 'app_ui_home_hide_sections' => [ 'hero' ] ],
                $appUiKeys,
                [ 'app_ui_theme_mode' => 'light', 'app_ui_product_show_sku' => false, 'app_ui_home_hide_sections' => [ 'hero' ] ],
            ],
            'Checkout' => [
                [ 'checkout_mode' => 'headless', 'checkout_redirect_label' => 'Pay now' ],
                [ 'checkout_mode', 'checkout_redirect_label', 'checkout_page_url' ],
                [ 'checkout_mode' => 'headless', 'checkout_redirect_label' => 'Pay now' ],
            ],
            'Store Policies' => [
                [ 'return_policy_days' => '30', 'free_shipping_threshold_amount' => '75.50', 'free_shipping_threshold_label' => 'Free delivery' ],
                [ 'return_policy_days', 'free_shipping_threshold_label', 'free_shipping_threshold_amount' ],
                [ 'return_policy_days' => 30, 'free_shipping_threshold_amount' => 75.5, 'free_shipping_threshold_label' => 'Free delivery' ],
            ],
            'API & CORS' => [
                [ 'cors_enabled' => '0', 'cors_allowed_origins' => 'https://shop.example.com' ],
                [ 'cors_enabled', 'cors_allowed_origins' ],
                [ 'cors_enabled' => false, 'cors_allowed_origins' => 'https://shop.example.com' ],
            ],
            'Webhooks (ISR)' => [
                [ 'storefront_revalidate_path' => '/api/revalidate-v2', 'revalidation_secret' => '' ],
                [ 'storefront_revalidate_path', 'revalidation_secret' ],
                [ 'storefront_revalidate_path' => '/api/revalidate-v2', 'revalidation_secret' => 'existing-secret' ],
            ],
            'Modules' => [
                [ '_sfb_save_modules' => '1', 'module_products_enabled' => '0' ],
                $moduleKeys,
                [ 'module_products_enabled' => false, 'module_orders_enabled' => false ],
            ],
        ];
    }

    public function test_webhook_secret_can_be_cleared_explicitly_without_echoing_or_replacing_on_blank_save(): void
    {
        $settings = StoreFuse_Bridge_Settings::defaults();
        $settings['revalidation_secret'] = 'configured-secret';
        $GLOBALS['_sfb_options']['storefuse_bridge_settings'] = $settings;
        StoreFuse_Bridge_Settings::reset_cache();

        $admin = new StoreFuse_Bridge_Admin();
        $kept = $admin->sanitize_settings( [ 'revalidation_secret' => '' ] );
        self::assertSame( 'configured-secret', $kept['revalidation_secret'] );

        StoreFuse_Bridge_Settings::reset_cache();
        $replaced = $admin->sanitize_settings( [ 'revalidation_secret' => 'replacement-secret' ] );
        self::assertSame( 'replacement-secret', $replaced['revalidation_secret'] );

        StoreFuse_Bridge_Settings::reset_cache();
        $cleared = $admin->sanitize_settings( [ '_sfb_clear_revalidation_secret' => '1' ] );
        self::assertSame( '', $cleared['revalidation_secret'] );
        self::assertArrayNotHasKey( '_sfb_clear_revalidation_secret', $cleared );
    }

    public function test_app_layout_can_persist_all_home_sections_as_visible(): void
    {
        $settings = StoreFuse_Bridge_Settings::defaults();
        $settings['app_ui_home_hide_sections'] = [ 'hero' ];
        $GLOBALS['_sfb_options']['storefuse_bridge_settings'] = $settings;
        StoreFuse_Bridge_Settings::reset_cache();

        $result = ( new StoreFuse_Bridge_Admin() )->sanitize_settings( [ '_sfb_save_app_ui' => '1' ] );

        self::assertSame( [], $result['app_ui_home_hide_sections'] );
        self::assertArrayNotHasKey( '_sfb_save_app_ui', $result );
    }

    public function test_legacy_redirect_runs_before_wordpress_plugin_page_access_check(): void
    {
        $admin = new StoreFuse_Bridge_Admin();

        self::assertContains( [ [ $admin, 'redirect_legacy_pages' ], 0 ], $GLOBALS['_sfb_actions']['admin_menu'] ?? [] );
        self::assertNotContains( [ [ $admin, 'redirect_legacy_pages' ], 1 ], $GLOBALS['_sfb_actions']['admin_init'] ?? [] );
    }

    public function test_menu_visibility_changes_by_client_without_dropping_hidden_pages(): void
    {
        $all_slugs = [
            'storefuse-bridge',
            'storefuse-bridge-setup',
            'storefuse-bridge-homepage',
            'storefuse-bridge-social',
            'storefuse-bridge-branding',
            'storefuse-bridge-app-layout',
            'storefuse-bridge-navigation',
            'storefuse-bridge-checkout',
            'storefuse-bridge-policies',
            'storefuse-bridge-api',
            'storefuse-bridge-webhooks',
            'storefuse-bridge-modules',
            'storefuse-bridge-extensions',
        ];

        $visible_by_client = [
            'flutter' => [ 'storefuse-bridge-app-layout' ],
            'nextjs'  => [ 'storefuse-bridge-navigation', 'storefuse-bridge-webhooks' ],
            'other'   => [ 'storefuse-bridge-navigation', 'storefuse-bridge-webhooks' ],
        ];

        foreach ( $visible_by_client as $client => $client_specific_visible ) {
            $settings = StoreFuse_Bridge_Settings::defaults();
            $settings['primary_client'] = $client;
            $GLOBALS['_sfb_options']['storefuse_bridge_settings'] = $settings;
            $GLOBALS['_sfb_menus'] = [];
            StoreFuse_Bridge_Settings::reset_cache();

            ( new StoreFuse_Bridge_Admin() )->register_menus();

            $registered = $GLOBALS['_sfb_menus']['sub'] ?? [];
            self::assertCount( count( $all_slugs ), $registered );
            $parent_by_slug = [];
            foreach ( $registered as [ $parent, , , $capability, $slug ] ) {
                $parent_by_slug[ $slug ] = $parent;
                self::assertSame( 'manage_woocommerce', $capability );
            }

            foreach ( array_slice( $all_slugs, 1 ) as $slug ) {
                $visible = ! in_array( $slug, [ 'storefuse-bridge-app-layout', 'storefuse-bridge-navigation', 'storefuse-bridge-webhooks' ], true )
                    || in_array( $slug, $client_specific_visible, true );
                self::assertSame( $visible ? 'storefuse-bridge' : '', $parent_by_slug[ $slug ] ?? null, $client . ': ' . $slug );
            }
        }
    }
}
