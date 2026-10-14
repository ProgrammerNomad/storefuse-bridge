<?php
defined( 'ABSPATH' ) || exit;

$s      = StoreFuse_Bridge_Settings::all();
$hidden = StoreFuse_Bridge_App_Ui::parse_home_hide_sections( $s['app_ui_home_hide_sections'] ?? [] );
$order  = StoreFuse_Bridge_App_Ui::parse_home_section_order( $s['app_ui_home_section_order'] ?? StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - App Layout', 'storefuse-bridge' ); ?></h1>
    <?php StoreFuse_Bridge_Admin::render_client_context_notice( 'storefuse-bridge-app-layout' ); ?>
    <p class="description">
        <?php esc_html_e( 'Layout and visibility settings for compatible Flutter clients. These values are exposed as settings.app.ui (storefuse.app-ui.v1).', 'storefuse-bridge' ); ?>
    </p>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <input type="hidden" name="storefuse_bridge_settings[_sfb_save_app_ui]" value="1" />

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Theming & Appearance', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_ui_theme_secondary_color"><?php esc_html_e( 'Secondary / Accent color', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_theme_secondary_color" name="storefuse_bridge_settings[app_ui_theme_secondary_color]" value="<?php echo esc_attr( $s['app_ui_theme_secondary_color'] ?? '#0F172A' ); ?>" class="sfb-color-picker" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_theme_mode"><?php esc_html_e( 'Theme mode', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_app_ui_theme_mode" name="storefuse_bridge_settings[app_ui_theme_mode]">
                            <option value="system" <?php selected( $s['app_ui_theme_mode'] ?? 'system', 'system' ); ?>><?php esc_html_e( 'System auto-detect', 'storefuse-bridge' ); ?></option>
                            <option value="light" <?php selected( $s['app_ui_theme_mode'] ?? 'system', 'light' ); ?>><?php esc_html_e( 'Light only', 'storefuse-bridge' ); ?></option>
                            <option value="dark" <?php selected( $s['app_ui_theme_mode'] ?? 'system', 'dark' ); ?>><?php esc_html_e( 'Dark only', 'storefuse-bridge' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_theme_font_family"><?php esc_html_e( 'Font family', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_theme_font_family" name="storefuse_bridge_settings[app_ui_theme_font_family]" value="<?php echo esc_attr( $s['app_ui_theme_font_family'] ?? 'Default' ); ?>" class="regular-text" placeholder="Default, Inter, Roboto..." />
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Bottom Navigation Builder', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_ui_nav_tabs"><?php esc_html_e( 'Active tabs & order', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_nav_tabs" name="storefuse_bridge_settings[app_ui_nav_tabs]" value="<?php echo esc_attr( $s['app_ui_nav_tabs'] ?? 'home,shop,search,cart,account' ); ?>" class="large-text" />
                        <p class="description"><?php esc_html_e( 'Comma-separated list of tabs. Available: home, shop, search, cart, account, wishlist', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Catalog (Shop, Search, Category)', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_ui_catalog_layout"><?php esc_html_e( 'Default layout', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_app_ui_catalog_layout" name="storefuse_bridge_settings[app_ui_catalog_layout]">
                            <option value="grid" <?php selected( $s['app_ui_catalog_layout'] ?? 'grid', 'grid' ); ?>><?php esc_html_e( 'Grid', 'storefuse-bridge' ); ?></option>
                            <option value="list" <?php selected( $s['app_ui_catalog_layout'] ?? 'grid', 'list' ); ?>><?php esc_html_e( 'List', 'storefuse-bridge' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_catalog_columns"><?php esc_html_e( 'Grid columns', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_app_ui_catalog_columns" name="storefuse_bridge_settings[app_ui_catalog_columns]">
                            <option value="2" <?php selected( (int) ( $s['app_ui_catalog_columns'] ?? 2 ), 2 ); ?>>2</option>
                            <option value="3" <?php selected( (int) ( $s['app_ui_catalog_columns'] ?? 2 ), 3 ); ?>>3</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_shop_title"><?php esc_html_e( 'Shop screen title', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_shop_title" name="storefuse_bridge_settings[app_ui_shop_title]" value="<?php echo esc_attr( $s['app_ui_shop_title'] ?? 'Products' ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_search_layout"><?php esc_html_e( 'Search layout', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <select id="sfb_app_ui_search_layout" name="storefuse_bridge_settings[app_ui_search_layout]">
                            <option value="" <?php selected( $s['app_ui_search_layout'] ?? '', '' ); ?>><?php esc_html_e( 'Same as catalog default', 'storefuse-bridge' ); ?></option>
                            <option value="grid" <?php selected( $s['app_ui_search_layout'] ?? '', 'grid' ); ?>><?php esc_html_e( 'Grid', 'storefuse-bridge' ); ?></option>
                            <option value="list" <?php selected( $s['app_ui_search_layout'] ?? '', 'list' ); ?>><?php esc_html_e( 'List', 'storefuse-bridge' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Product cards', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="sfb_app_ui_catalog_show_stock_badge" name="storefuse_bridge_settings[app_ui_catalog_show_stock_badge]" value="1" <?php checked( ! empty( $s['app_ui_catalog_show_stock_badge'] ) ); ?> />
                            <?php esc_html_e( 'Show stock badge on grid/list cards', 'storefuse-bridge' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Search Experience', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_ui_search_placeholder"><?php esc_html_e( 'Search placeholder', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_search_placeholder" name="storefuse_bridge_settings[app_ui_search_placeholder]" value="<?php echo esc_attr( $s['app_ui_search_placeholder'] ?? 'Search products...' ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_search_trending_terms"><?php esc_html_e( 'Trending search terms', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_search_trending_terms" name="storefuse_bridge_settings[app_ui_search_trending_terms]" value="<?php echo esc_attr( $s['app_ui_search_trending_terms'] ?? '' ); ?>" class="large-text" placeholder="Sneakers, Summer Dress, Hoodie" />
                        <p class="description"><?php esc_html_e( 'Comma-separated terms displayed as quick suggestion chips.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Product detail', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Options', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label><input type="checkbox" id="sfb_app_ui_product_show_sku" name="storefuse_bridge_settings[app_ui_product_show_sku]" value="1" <?php checked( ! empty( $s['app_ui_product_show_sku'] ) ); ?> /> <?php esc_html_e( 'Show SKU', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_product_sticky_add_to_cart" name="storefuse_bridge_settings[app_ui_product_sticky_add_to_cart]" value="1" <?php checked( ! empty( $s['app_ui_product_sticky_add_to_cart'] ) ); ?> /> <?php esc_html_e( 'Sticky add to cart bar', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_product_show_reviews" name="storefuse_bridge_settings[app_ui_product_show_reviews]" value="1" <?php checked( ! empty( $s['app_ui_product_show_reviews'] ?? true ) ); ?> /> <?php esc_html_e( 'Show customer reviews', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_product_show_share" name="storefuse_bridge_settings[app_ui_product_show_share]" value="1" <?php checked( ! empty( $s['app_ui_product_show_share'] ?? true ) ); ?> /> <?php esc_html_e( 'Show share button', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_product_show_wishlist" name="storefuse_bridge_settings[app_ui_product_show_wishlist]" value="1" <?php checked( ! empty( $s['app_ui_product_show_wishlist'] ?? true ) ); ?> /> <?php esc_html_e( 'Show wishlist button', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_product_related_count"><?php esc_html_e( 'Related products count', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="number" id="sfb_app_ui_product_related_count" name="storefuse_bridge_settings[app_ui_product_related_count]" value="<?php echo esc_attr( (string) ( $s['app_ui_product_related_count'] ?? 4 ) ); ?>" min="0" max="12" class="small-text" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Cart & Checkout Experience', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Cart options', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label><input type="checkbox" id="sfb_app_ui_cart_show_coupon" name="storefuse_bridge_settings[app_ui_cart_show_coupon]" value="1" <?php checked( ! empty( $s['app_ui_cart_show_coupon'] ) ); ?> /> <?php esc_html_e( 'Show coupon field', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_cart_show_free_shipping_bar" name="storefuse_bridge_settings[app_ui_cart_show_free_shipping_bar]" value="1" <?php checked( ! empty( $s['app_ui_cart_show_free_shipping_bar'] ) ); ?> /> <?php esc_html_e( 'Show free shipping progress (uses store.free_shipping_threshold)', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" id="sfb_app_ui_cart_show_guest_prompt" name="storefuse_bridge_settings[app_ui_cart_show_guest_prompt]" value="1" <?php checked( ! empty( $s['app_ui_cart_show_guest_prompt'] ?? true ) ); ?> /> <?php esc_html_e( 'Show guest login prompt banner in cart', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Checkout options', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label><input type="checkbox" id="sfb_app_ui_checkout_show_guest_prompt" name="storefuse_bridge_settings[app_ui_checkout_show_guest_prompt]" value="1" <?php checked( ! empty( $s['app_ui_checkout_show_guest_prompt'] ?? true ) ); ?> /> <?php esc_html_e( 'Show guest login prompt banner on checkout', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sfb_app_ui_cart_empty_cta_href"><?php esc_html_e( 'Empty cart CTA link', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_cart_empty_cta_href" name="storefuse_bridge_settings[app_ui_cart_empty_cta_href]" value="<?php echo esc_attr( $s['app_ui_cart_empty_cta_href'] ?? '/shop' ); ?>" class="regular-text" placeholder="/shop" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Home screen sections', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Reorder or hide homepage blocks without deleting content on the Homepage admin tab.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_app_ui_home_section_order"><?php esc_html_e( 'Section order', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_app_ui_home_section_order" name="storefuse_bridge_settings[app_ui_home_section_order]" value="<?php echo esc_attr( is_array( $order ) ? implode( ',', $order ) : (string) $order ); ?>" class="large-text" />
                        <p class="description"><?php echo esc_html( implode( ', ', StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS ) ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Hidden sections', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php foreach ( StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS as $section_id ) : ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" id="sfb_app_ui_home_hide_<?php echo esc_attr( $section_id ); ?>" name="storefuse_bridge_settings[app_ui_home_hide_sections][]" value="<?php echo esc_attr( $section_id ); ?>" <?php checked( in_array( $section_id, $hidden, true ) ); ?> />
                                <?php echo esc_html( $section_id ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>
