<?php defined( 'ABSPATH' ) || exit;
$s = StoreFuse_Bridge_Settings::all();
?>
<div class="wrap sfb-admin">
    <h1><?php esc_html_e( 'StoreFuse - General Settings', 'storefuse-bridge' ); ?></h1>

    <?php settings_errors( 'storefuse_bridge_settings' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'storefuse_bridge_settings_group' ); ?>

        <!-- ── Announcement Bar ─────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Announcement Bar', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enabled', 'storefuse-bridge' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="storefuse_bridge_settings[announcement_bar_enabled]" value="1"
                                <?php checked( ! empty( $s['announcement_bar_enabled'] ) ); ?> />
                            <?php esc_html_e( 'Show announcement bar on storefront', 'storefuse-bridge' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_bar_text"><?php esc_html_e( 'Bar Text', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_bar_text" name="storefuse_bridge_settings[announcement_bar_text]"
                               value="<?php echo esc_attr( $s['announcement_bar_text'] ?? '' ); ?>"
                               class="regular-text" placeholder="e.g. Free shipping on orders over $99" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_bar_color"><?php esc_html_e( 'Background Colour', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_bar_color" name="storefuse_bridge_settings[announcement_bar_bg_color]"
                               value="<?php echo esc_attr( $s['announcement_bar_bg_color'] ?? '#E85D04' ); ?>"
                               class="sfb-color-picker" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_bar_link"><?php esc_html_e( 'Link URL (optional)', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="url" id="sfb_bar_link" name="storefuse_bridge_settings[announcement_bar_link]"
                               value="<?php echo esc_attr( $s['announcement_bar_link'] ?? '' ); ?>"
                               class="regular-text" placeholder="/shop?tag=sale" />
                    </td>
                </tr>
            </table>
        </div>

        <!-- ── Store Policies ──────────────────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Store Policies', 'storefuse-bridge' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="sfb_return_days"><?php esc_html_e( 'Return Policy Days', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="number" id="sfb_return_days" name="storefuse_bridge_settings[return_policy_days]"
                               value="<?php echo esc_attr( $s['return_policy_days'] ?? 7 ); ?>"
                               class="small-text" min="0" />
                        <p class="description"><?php esc_html_e( 'Shown in trust badges and footer. 0 = no return policy shown.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_shipping_label"><?php esc_html_e( 'Free Shipping Label', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="text" id="sfb_shipping_label" name="storefuse_bridge_settings[free_shipping_threshold_label]"
                               value="<?php echo esc_attr( $s['free_shipping_threshold_label'] ?? '' ); ?>"
                               class="regular-text" placeholder="Free shipping on orders over ₹999" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sfb_shipping_amount"><?php esc_html_e( 'Free Shipping Threshold Amount', 'storefuse-bridge' ); ?></label></th>
                    <td>
                        <input type="number" id="sfb_shipping_amount" name="storefuse_bridge_settings[free_shipping_threshold_amount]"
                               value="<?php echo esc_attr( $s['free_shipping_threshold_amount'] ?? 0 ); ?>"
                               class="regular-text" min="0" step="0.01" />
                        <p class="description"><?php esc_html_e( 'Used to show cart progress bar. Enter 0 to disable.', 'storefuse-bridge' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ── Site Identity (read-only) ─────────────────────────────── -->
        <div class="sfb-card">
            <h2><?php esc_html_e( 'Site Identity', 'storefuse-bridge' ); ?></h2>
            <p><?php esc_html_e( 'Logo and favicon are managed in WordPress Customizer.', 'storefuse-bridge' ); ?>
               <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>" class="button button-small">
                   <?php esc_html_e( 'Open Customizer →', 'storefuse-bridge' ); ?>
               </a>
            </p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Site Name', 'storefuse-bridge' ); ?></th>
                    <td><?php echo esc_html( get_bloginfo( 'name' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Logo URL', 'storefuse-bridge' ); ?></th>
                    <td>
                        <?php
                        $logo_id  = get_theme_mod( 'custom_logo' );
                        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : null;
                        ?>
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" style="max-height:40px;vertical-align:middle;margin-right:8px;" />
                            <code><?php echo esc_html( $logo_url ); ?></code>
                        <?php else : ?>
                            <em><?php esc_html_e( 'No logo set', 'storefuse-bridge' ); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'storefuse-bridge' ) ); ?>
    </form>
</div>
