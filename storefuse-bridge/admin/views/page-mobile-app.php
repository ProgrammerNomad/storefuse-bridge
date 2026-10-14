<?php
defined( 'ABSPATH' ) || exit;

$s      = StoreFuse_Bridge_Settings::all();
$hidden = StoreFuse_Bridge_App_Ui::parse_home_hide_sections( $s['app_ui_home_hide_sections'] ?? [] );
$order  = StoreFuse_Bridge_App_Ui::parse_home_section_order( $s['app_ui_home_section_order'] ?? StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS );
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - Mobile app', 'storefuse-bridge' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Layout and visibility for the StoreFuse Flutter reference app. Exposed as settings.app.ui (storefuse.app-ui.v1).', 'storefuse-bridge' ); ?>
    </p>
    <?php settings_errors( 'storefuse_bridge_settings' ); ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>
        <input type="hidden" name="storefuse_bridge_settings[_sfb_save_app_ui]" value="1" />

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Catalog (Shop, Search, Category)', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Default layout', 'storefuse-bridge' ); ?></th>
                    <td>
                        <select name="storefuse_bridge_settings[app_ui_catalog_layout]">
                            <option value="grid" <?php selected( $s['app_ui_catalog_layout'] ?? 'grid', 'grid' ); ?>><?php esc_html_e( 'Grid', 'storefuse-bridge' ); ?></option>
                            <option value="list" <?php selected( $s['app_ui_catalog_layout'] ?? 'grid', 'list' ); ?>><?php esc_html_e( 'List', 'storefuse-bridge' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Grid columns', 'storefuse-bridge' ); ?></th>
                    <td>
                        <select name="storefuse_bridge_settings[app_ui_catalog_columns]">
                            <option value="2" <?php selected( (int) ( $s['app_ui_catalog_columns'] ?? 2 ), 2 ); ?>>2</option>
                            <option value="3" <?php selected( (int) ( $s['app_ui_catalog_columns'] ?? 2 ), 3 ); ?>>3</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Shop screen title', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[app_ui_shop_title]" value="<?php echo esc_attr( $s['app_ui_shop_title'] ?? 'Products' ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Search layout', 'storefuse-bridge' ); ?></th>
                    <td>
                        <select name="storefuse_bridge_settings[app_ui_search_layout]">
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
                            <input type="checkbox" name="storefuse_bridge_settings[app_ui_catalog_show_stock_badge]" value="1" <?php checked( ! empty( $s['app_ui_catalog_show_stock_badge'] ) ); ?> />
                            <?php esc_html_e( 'Show stock badge on grid/list cards', 'storefuse-bridge' ); ?>
                        </label>
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
                        <label><input type="checkbox" name="storefuse_bridge_settings[app_ui_product_show_sku]" value="1" <?php checked( ! empty( $s['app_ui_product_show_sku'] ) ); ?> /> <?php esc_html_e( 'Show SKU', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" name="storefuse_bridge_settings[app_ui_product_sticky_add_to_cart]" value="1" <?php checked( ! empty( $s['app_ui_product_sticky_add_to_cart'] ) ); ?> /> <?php esc_html_e( 'Sticky add to cart bar', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Related products count', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="number" name="storefuse_bridge_settings[app_ui_product_related_count]" value="<?php echo esc_attr( (string) ( $s['app_ui_product_related_count'] ?? 4 ) ); ?>" min="0" max="12" class="small-text" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Cart', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Options', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="storefuse_bridge_settings[app_ui_cart_show_coupon]" value="1" <?php checked( ! empty( $s['app_ui_cart_show_coupon'] ) ); ?> /> <?php esc_html_e( 'Show coupon field', 'storefuse-bridge' ); ?></label><br>
                        <label><input type="checkbox" name="storefuse_bridge_settings[app_ui_cart_show_free_shipping_bar]" value="1" <?php checked( ! empty( $s['app_ui_cart_show_free_shipping_bar'] ) ); ?> /> <?php esc_html_e( 'Show free shipping progress (uses store.free_shipping_threshold)', 'storefuse-bridge' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Empty cart CTA link', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[app_ui_cart_empty_cta_href]" value="<?php echo esc_attr( $s['app_ui_cart_empty_cta_href'] ?? '/shop' ); ?>" class="regular-text" placeholder="/shop" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="sfb-card">
            <h2><?php esc_html_e( 'Home screen sections', 'storefuse-bridge' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Reorder or hide homepage blocks without deleting content on the Homepage admin tab.', 'storefuse-bridge' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Section order', 'storefuse-bridge' ); ?></th>
                    <td>
                        <input type="text" name="storefuse_bridge_settings[app_ui_home_section_order]" value="<?php echo esc_attr( is_array( $order ) ? implode( ',', $order ) : (string) $order ); ?>" class="large-text" />
                        <p class="description"><?php echo esc_html( implode( ', ', StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS ) ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Hidden sections', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php foreach ( StoreFuse_Bridge_App_Ui::HOME_SECTION_IDS as $section_id ) : ?>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="checkbox" name="storefuse_bridge_settings[app_ui_home_hide_sections][]" value="<?php echo esc_attr( $section_id ); ?>" <?php checked( in_array( $section_id, $hidden, true ) ); ?> />
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
